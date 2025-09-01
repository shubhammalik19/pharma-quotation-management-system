<?php
/**
 * PDF Generation Class using wkhtmltopdf
 */
class QuotationPDFGenerator {
    private $wkhtmltopdf_path;
    private $temp_dir;
    
    public function __construct() {
        // Path to wkhtmltopdf binary
        $this->wkhtmltopdf_path = '/usr/bin/wkhtmltopdf';
        
        // Temporary directory for PDF files
        $this->temp_dir = __DIR__ . '/../../storage/temp/';
        
        // Create temp directory if it doesn't exist
        if (!is_dir($this->temp_dir)) {
            mkdir($this->temp_dir, 0755, true);
        }
    }
    
    /**
     * Generate PDF from quotation ID
     */
    public function generateQuotationPDF($quotation_id, $options = []) {
        // Default options
        $default_options = [
            'page-size' => 'A4',
            'margin-top' => '0.75in',
            'margin-right' => '0.75in',
            'margin-bottom' => '0.75in',
            'margin-left' => '0.75in',
            'encoding' => 'UTF-8',
            'no-outline' => true,
            'enable-local-file-access' => true,
            'disable-smart-shrinking' => true,
            'print-media-type' => true,
            'orientation' => 'Portrait'
        ];
        
        $options = array_merge($default_options, $options);
        
        // Build the URL for the print page
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        $base_url = $protocol . '://' . $host . $script_dir;
        
        $print_url = $base_url . '/print_quotation.php?id=' . $quotation_id . '&pdf=1';
        
        // Generate unique filename
        $filename = 'quotation_' . $quotation_id . '_' . date('Ymd_His') . '.pdf';
        $pdf_path = $this->temp_dir . $filename;
        
        // Build wkhtmltopdf command
        $command = escapeshellcmd($this->wkhtmltopdf_path);
        
        // Add options
        foreach ($options as $key => $value) {
            if ($value === true) {
                $command .= ' --' . $key;
            } elseif ($value !== false) {
                $command .= ' --' . $key . ' ' . escapeshellarg($value);
            }
        }
        
        // Add source URL and output file
        $command .= ' ' . escapeshellarg($print_url) . ' ' . escapeshellarg($pdf_path);
        
        // Execute command
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception('PDF generation failed: ' . implode('\n', $output));
        }
        
        if (!file_exists($pdf_path)) {
            throw new Exception('PDF file was not created');
        }
        
        return [
            'path' => $pdf_path,
            'filename' => $filename,
            'size' => filesize($pdf_path)
        ];
    }
    
    /**
     * Clean up old PDF files
     */
    public function cleanup($max_age_hours = 24) {
        $files = glob($this->temp_dir . '*.pdf');
        $cutoff_time = time() - ($max_age_hours * 3600);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Download PDF file
     */
    public function downloadPDF($pdf_path, $filename = null) {
        if (!file_exists($pdf_path)) {
            throw new Exception('PDF file not found');
        }
        
        if (!$filename) {
            $filename = basename($pdf_path);
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($pdf_path);
        exit;
    }
    
    /**
     * Stream PDF to browser
     */
    public function streamPDF($pdf_path, $filename = null) {
        if (!file_exists($pdf_path)) {
            throw new Exception('PDF file not found');
        }
        
        if (!$filename) {
            $filename = basename($pdf_path);
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($pdf_path);
        exit;
    }
}
?>
