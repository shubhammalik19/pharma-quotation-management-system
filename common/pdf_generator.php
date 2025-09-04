<?php
/**
 * /common/pdf_generator.php
 * ------------------------------------------------------------
 * Generates PDFs for Quotation (and can be extended for others)
 * - Renders docs/print_quotation.php?id=...&pdf=1 to HTML
 * - Uses Python script (generate_pdf.py) to convert HTML -> PDF
 * - Clean, defensive error handling + temp file management
 *
 * Requirements:
 * - PHP 8+
 * - Python3 available on server
 * - common/generate_pdf.py present and working
 */

declare(strict_types=1);

class QuotationPDFGenerator
{
    /** @var string Absolute path to Python HTML->PDF converter */
    private string $python_script_path;

    /** @var string Directory for temp files (must be writable) */
    private string $temp_dir;

    /** @var string Absolute path to the print template (quotation) */
    private string $print_quotation_php;

    /** @var string Base URL to your site (used for curl fallback) */
    private string $base_url;

    /** @var string Path to PHP binary (optional; used when you extend) */
    private string $php_bin;

    public function __construct(array $opts = [])
    {
        // --- Adjust these to your deployment ---
        $docroot = dirname(__DIR__, 1); // /common -> project root
        $this->python_script_path = $opts['python_script_path']
            ?? $docroot . '/common/generate_pdf.py';

        $this->temp_dir = $opts['temp_dir']
            ?? $docroot . '/storage/temp/';

        $this->print_quotation_php = $opts['print_quotation_php']
            ?? $docroot . '/docs/print_quotation.php';

        // Best-effort base URL detection (fallback to your domain)
        $this->base_url = $opts['base_url']
            ?? (isset($_SERVER['HTTP_HOST'])
                    ? ( (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://' )
                        . $_SERVER['HTTP_HOST']
                    : 'https://quotation.logisticsoftware.in');

        // Optional: override python3 / php binary paths if needed
        $this->php_bin = $opts['php_bin'] ?? PHP_BINARY;

        // --- Basic sanity checks ---
        $this->bootstrap();
    }

    private function bootstrap(): void
    {
        if (!is_dir($this->temp_dir) && !@mkdir($this->temp_dir, 0755, true)) {
            throw new RuntimeException('Failed to create temp directory: ' . $this->temp_dir);
        }
        if (!is_writable($this->temp_dir)) {
            throw new RuntimeException('Temp directory not writable: ' . $this->temp_dir);
        }
        if (!is_file($this->python_script_path)) {
            throw new RuntimeException('Python PDF script not found: ' . $this->python_script_path);
        }
        if (!is_file($this->print_quotation_php)) {
            throw new RuntimeException('Print template not found: ' . $this->print_quotation_php);
        }
    }

    /**
     * PUBLIC: Generate Quotation PDF from quotation id.
     * Returns array: ['path' => '/abs/path.pdf', 'filename' => 'quotation_123_timestamp.pdf', 'size' => int]
     */
    public function generateQuotationPDF(int $quotation_id, array $options = []): array
    {
        $orientation = $options['orientation'] ?? 'Portrait'; // or 'Landscape'
        $html = $this->renderQuotationHtml($quotation_id);

        return $this->htmlToPdf($html, [
            'prefix'      => 'quotation_' . $quotation_id . '_',
            'orientation' => $orientation,
        ]);
    }

    /**
     * PUBLIC: Turn arbitrary HTML into a PDF file in temp_dir.
     * Returns array like generateQuotationPDF().
     */
    public function htmlToPdf(string $html, array $options = []): array
    {
        $prefix      = $options['prefix'] ?? 'doc_';
        $orientation = $options['orientation'] ?? 'Portrait';

        $timestamp = date('Ymd_His');
        $base      = $prefix . $timestamp;
        $html_path = $this->temp_dir . $base . '.html';
        $pdf_path  = $this->temp_dir . $base . '.pdf';

        if (@file_put_contents($html_path, $html) === false) {
            throw new RuntimeException('Failed to save HTML to: ' . $html_path);
        }

        $cmd = 'python3 ' . escapeshellarg($this->python_script_path) . ' '
             . escapeshellarg($html_path) . ' '
             . escapeshellarg($pdf_path) . ' '
             . escapeshellarg($orientation);

        [$out, $exitCode, $mode] = $this->runCommand($cmd);
        error_log("[pdf_generator] python via {$mode}; exit={$exitCode}; output:\n{$out}");

        if (!is_file($pdf_path) || filesize($pdf_path) <= 0) {
            // keep HTML for troubleshooting if conversion failed
            throw new RuntimeException('PDF not created. Converter output: ' . trim($out));
        }

        // Clean HTML on success
        @unlink($html_path);

        return [
            'path'     => $pdf_path,
            'filename' => basename($pdf_path),
            'size'     => filesize($pdf_path),
        ];
    }

    /**
     * PUBLIC: Stream PDF inline to browser.
     */
    public function streamPDF(string $pdf_path, ?string $download_name = null): void
    {
        $this->sendPdfHeaders($pdf_path, $download_name, /*inline*/ true);
    }

    /**
     * PUBLIC: Force-download PDF.
     */
    public function downloadPDF(string $pdf_path, ?string $download_name = null): void
    {
        $this->sendPdfHeaders($pdf_path, $download_name, /*inline*/ false);
    }

    /**
     * PUBLIC: Cleanup old generated files.
     */
    public function cleanup(int $max_age_hours = 24): void
    {
        $cutoff = time() - ($max_age_hours * 3600);
        foreach (glob($this->temp_dir . '*.{pdf,html}', GLOB_BRACE) ?: [] as $file) {
            $mtime = @filemtime($file);
            if ($mtime !== false && $mtime < $cutoff) {
                @unlink($file);
            }
        }
    }

    /* ---------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------- */

    /** Renders the quotation HTML via local include first, else via cURL. */
    private function renderQuotationHtml(int $quotation_id): string
    {
        // 1) Local include (best: shares current session + $conn etc.)
        $html = $this->renderViaInclude($this->print_quotation_php, [
            'id'  => (string)$quotation_id,
            'pdf' => '1',
        ]);

        if (is_string($html) && trim($html) !== '') {
            return $html;
        }

        // 2) Fallback: HTTP fetch with session cookie
        $url = rtrim($this->base_url, '/') . '/docs/print_quotation.php?id=' . urlencode((string)$quotation_id) . '&pdf=1';
        $html = $this->fetchViaCurl($url);

        if (trim((string)$html) === '') {
            throw new RuntimeException('Empty HTML from print_quotation.php');
        }
        return (string)$html;
    }

    /** Local include renderer: sets $_GET and includes the file, capturing output. */
    private function renderViaInclude(string $filePath, array $getParams = []): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        // Ensure DB connection/scope from parent include stacks is visible
        // (common pattern in your codebase)
        global $conn;

        $oldGET = $_GET;
        foreach ($getParams as $k => $v) {
            $_GET[$k] = $v;
        }

        // Hide notices in PDF mode
        $oldDisplayErrors = ini_get('display_errors');
        $oldErrLevel      = error_reporting();
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        ob_start();
        include $filePath;
        $html = ob_get_clean();

        // Restore state
        $_GET = $oldGET;
        ini_set('display_errors', (string)$oldDisplayErrors);
        error_reporting($oldErrLevel);

        return $html ?: null;
    }

    /** Fetch HTML using cURL, passing current PHPSESSID if present. */
    private function fetchViaCurl(string $url): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL not available to fetch HTML: ' . $url);
        }

        $ch = curl_init();
        $headers = [
            'User-Agent: PDF-Generator/1.0 (+PHP cURL)',
        ];

        // Pass session cookie (so print page can checkLogin())
        if (session_status() === PHP_SESSION_ACTIVE) {
            $cookiePieces = [];
            $cookiePieces[] = session_name() . '=' . session_id();
            if (!empty($_SERVER['HTTP_COOKIE'])) {
                $cookiePieces[] = $_SERVER['HTTP_COOKIE'];
            }
            $headers[] = 'Cookie: ' . implode('; ', $cookiePieces);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 45,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL error: ' . $err);
        }
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new RuntimeException('HTTP ' . $code . ' while fetching ' . $url);
        }

        return (string)$body;
    }

    /** Try multiple PHP exec methods (exec/shell_exec/system/passthru). */
    private function runCommand(string $cmd): array
    {
        $disabled = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        $tried    = [];

        if (!in_array('exec', $disabled, true) && function_exists('exec')) {
            $tried[] = 'exec';
            $o = [];
            $code = 0;
            @exec($cmd . ' 2>&1', $o, $code);
            return [implode("\n", $o), (int)$code, 'exec'];
        }

        if (!in_array('shell_exec', $disabled, true) && function_exists('shell_exec')) {
            $tried[] = 'shell_exec';
            $o = @shell_exec($cmd . ' 2>&1');
            return [$o ?? '', is_null($o) ? 1 : 0, 'shell_exec'];
        }

        if (!in_array('system', $disabled, true) && function_exists('system')) {
            $tried[] = 'system';
            ob_start();
            $ret = 0;
            @system($cmd . ' 2>&1', $ret);
            $o = ob_get_clean();
            return [$o ?? '', (int)$ret, 'system'];
        }

        if (!in_array('passthru', $disabled, true) && function_exists('passthru')) {
            $tried[] = 'passthru';
            ob_start();
            $ret = 0;
            @passthru($cmd . ' 2>&1', $ret);
            $o = ob_get_clean();
            return [$o ?? '', (int)$ret, 'passthru'];
        }

        throw new RuntimeException('No exec functions available. Tried: ' . implode(', ', $tried));
    }

    /** Output headers + file body; inline=false forces download. */
    private function sendPdfHeaders(string $pdf_path, ?string $download_name, bool $inline): void
    {
        if (!is_file($pdf_path)) {
            throw new RuntimeException('PDF not found: ' . $pdf_path);
        }
        $download_name = $download_name ?: basename($pdf_path);

        if (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $download_name . '"');

        readfile($pdf_path);
        exit;
    }
}
