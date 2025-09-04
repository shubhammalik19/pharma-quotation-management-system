#!/usr/bin/env python3
"""
PDF Generation Script using pdfkit
Converts HTML files to PDF with comprehensive error handling and logging.
"""

import pdfkit
import sys
import os
import logging
import traceback
from pathlib import Path
import subprocess
import shutil

def setup_logging():
    """Set up logging configuration"""
    log_dir = '/home/logisticsoftware/public_html/quotation.logisticsoftware.in/storage/logs'
    
    # Create log directory if it doesn't exist
    try:
        os.makedirs(log_dir, exist_ok=True)
    except Exception as e:
        print(f"WARNING: Could not create log directory {log_dir}: {e}")
        log_dir = '/tmp'  # Fallback to /tmp
    
    log_file = os.path.join(log_dir, 'pdf_generation.log')
    
    # Set up logging
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)s - %(message)s',
        handlers=[
            logging.FileHandler(log_file, mode='a'),
            logging.StreamHandler(sys.stdout)  # Also output to stdout for PHP to capture
        ]
    )
    return logging.getLogger(__name__)

def print_debug(message):
    """Print debug message to both stdout and stderr"""
    print(f"DEBUG: {message}")
    print(f"DEBUG: {message}", file=sys.stderr)

def validate_arguments():
    """Validate and parse command line arguments"""
    print_debug(f"Received {len(sys.argv)} arguments: {sys.argv}")
    
    if len(sys.argv) < 3:
        raise ValueError(
            f"Insufficient arguments. Expected: python {sys.argv[0]} <input_html> <output_pdf> [orientation]\n"
            f"Received {len(sys.argv)-1} arguments: {sys.argv[1:]}"
        )
    
    input_html = sys.argv[1].strip()
    output_pdf = sys.argv[2].strip()
    orientation = sys.argv[3].strip().lower() if len(sys.argv) > 3 else 'portrait'
    
    print_debug(f"Parsed arguments - Input: {input_html}, Output: {output_pdf}, Orientation: {orientation}")
    
    # Validate orientation
    valid_orientations = ['portrait', 'landscape']
    if orientation not in valid_orientations:
        print_debug(f"Invalid orientation '{orientation}', defaulting to 'portrait'")
        orientation = 'portrait'
    
    return input_html, output_pdf, orientation

def validate_input_file(input_html):
    """Validate input HTML file"""
    print_debug(f"Validating input file: {input_html}")
    
    input_path = Path(input_html)
    
    if not input_path.exists():
        raise FileNotFoundError(f"Input HTML file does not exist: {input_html}")
    
    if not input_path.is_file():
        raise ValueError(f"Input path is not a file: {input_html}")
    
    file_size = input_path.stat().st_size
    if file_size == 0:
        raise ValueError(f"Input HTML file is empty: {input_html}")
    
    print_debug(f"Input file is valid, size: {file_size} bytes")
    
    # Check if file is readable and contains HTML-like content
    try:
        with open(input_html, 'r', encoding='utf-8') as f:
            content = f.read(1000)  # Read first 1000 chars to test
            if not content.strip():
                raise ValueError(f"Input HTML file appears to be empty or contains only whitespace")
            
            # Check for HTML-like content
            if '<html' not in content.lower() and '<!doctype' not in content.lower():
                print_debug(f"Warning: File may not be valid HTML. First 200 chars: {content[:200]}")
                
    except PermissionError:
        raise PermissionError(f"Permission denied reading input file: {input_html}")
    except UnicodeDecodeError as e:
        raise ValueError(f"Input file encoding error: {e}")

def validate_output_path(output_pdf):
    """Validate output PDF path"""
    print_debug(f"Validating output path: {output_pdf}")
    
    output_path = Path(output_pdf)
    output_dir = output_path.parent
    
    # Create output directory if it doesn't exist
    try:
        output_dir.mkdir(parents=True, exist_ok=True)
        print_debug(f"Output directory confirmed: {output_dir}")
    except PermissionError:
        raise PermissionError(f"Permission denied creating output directory: {output_dir}")
    
    # Check if output directory is writable
    if not os.access(output_dir, os.W_OK):
        raise PermissionError(f"Output directory is not writable: {output_dir}")
    
    # If output file exists, check if it's writable
    if output_path.exists() and not os.access(output_path, os.W_OK):
        raise PermissionError(f"Cannot overwrite existing output file: {output_pdf}")

def find_wkhtmltopdf():
    """Find wkhtmltopdf binary"""
    print_debug("Searching for wkhtmltopdf binary")
    
    # Try common paths
    possible_paths = [
        '/usr/local/bin/wkhtmltopdf',
        '/usr/bin/wkhtmltopdf',
        '/opt/bin/wkhtmltopdf'
    ]
    
    for path in possible_paths:
        if os.path.isfile(path) and os.access(path, os.X_OK):
            print_debug(f"Found wkhtmltopdf at: {path}")
            return path
    
    # Try to find in system PATH
    path_from_which = shutil.which('wkhtmltopdf')
    if path_from_which:
        print_debug(f"Found wkhtmltopdf in PATH: {path_from_which}")
        return path_from_which
    
    raise FileNotFoundError(
        f"wkhtmltopdf not found in any of these locations: {possible_paths}\n"
        "Please install wkhtmltopdf or ensure it's in your system PATH"
    )

def test_wkhtmltopdf(wkhtmltopdf_path):
    """Test if wkhtmltopdf works"""
    try:
        print_debug(f"Testing wkhtmltopdf at: {wkhtmltopdf_path}")
        # Use subprocess.Popen for Python 3.6 compatibility
        process = subprocess.Popen([wkhtmltopdf_path, '--version'], 
                                 stdout=subprocess.PIPE, stderr=subprocess.PIPE, 
                                 universal_newlines=True)
        stdout, stderr = process.communicate(timeout=10)
        print_debug(f"wkhtmltopdf version output: {stdout}")
        if process.returncode == 0:
            return True
        else:
            print_debug(f"wkhtmltopdf test failed with return code: {process.returncode}")
            print_debug(f"stderr: {stderr}")
            return False
    except Exception as e:
        print_debug(f"wkhtmltopdf test failed: {e}")
        return False

def get_pdf_options(orientation):
    """Get PDF generation options based on orientation"""
    print_debug(f"Setting up PDF options for {orientation} orientation")
    
    base_options = {
        'page-size': 'A4',
        'encoding': 'UTF-8',
        'no-outline': None,
        'margin-top': '0.75in',
        'margin-right': '0.75in',
        'margin-bottom': '0.75in',
        'margin-left': '0.75in',
        'disable-smart-shrinking': None,
        'print-media-type': None,
        'enable-local-file-access': None,
        'javascript-delay': '2000',
        'load-error-handling': 'ignore',
        'load-media-error-handling': 'ignore',
        'quiet': None  # Reduce verbose output
    }
    
    if orientation == 'landscape':
        base_options['orientation'] = 'Landscape'
    else:
        base_options['orientation'] = 'Portrait'
    
    print_debug(f"PDF options: {base_options}")
    return base_options

def generate_pdf(input_html, output_pdf, orientation, logger):
    """Generate PDF from HTML file"""
    try:
        print_debug("Starting PDF generation process")
        
        # Validate inputs
        validate_input_file(input_html)
        validate_output_path(output_pdf)
        
        # Find and test wkhtmltopdf
        wkhtmltopdf_path = find_wkhtmltopdf()
        
        if not test_wkhtmltopdf(wkhtmltopdf_path):
            raise RuntimeError(f"wkhtmltopdf binary test failed: {wkhtmltopdf_path}")
        
        # Set up pdfkit configuration
        config = pdfkit.configuration(wkhtmltopdf=wkhtmltopdf_path)
        
        # Get PDF options
        options = get_pdf_options(orientation)
        
        logger.info(f"Generating PDF with wkhtmltopdf at: {wkhtmltopdf_path}")
        logger.info(f"Input: {input_html}")
        logger.info(f"Output: {output_pdf}")
        logger.info(f"Orientation: {orientation}")
        logger.info(f"Options: {options}")
        
        print_debug("Calling pdfkit.from_file()")
        
        # Generate PDF
        try:
            success = pdfkit.from_file(
                input_html, 
                output_pdf, 
                configuration=config, 
                options=options,
                verbose=False  # Reduce noise
            )
        except Exception as pdfkit_error:
            print_debug(f"pdfkit.from_file() raised exception: {pdfkit_error}")
            # Sometimes pdfkit raises exceptions even when PDF is created successfully
            success = os.path.exists(output_pdf)
            if not success:
                raise pdfkit_error
        
        print_debug(f"pdfkit.from_file() returned: {success}")
        
        # Verify output file was created and has content
        output_path = Path(output_pdf)
        if not output_path.exists():
            raise RuntimeError(f"Output PDF file was not created: {output_pdf}")
        
        file_size = output_path.stat().st_size
        if file_size == 0:
            raise RuntimeError(f"Output PDF file is empty: {output_pdf}")
        
        # Verify it's a valid PDF file
        try:
            with open(output_pdf, 'rb') as f:
                header = f.read(10)
                if not header.startswith(b'%PDF'):
                    raise RuntimeError(f"Generated file is not a valid PDF (invalid header): {output_pdf}")
        except Exception as e:
            raise RuntimeError(f"Error validating PDF file: {e}")
        
        success_message = f"PDF generated successfully: {output_pdf} (Size: {file_size} bytes)"
        logger.info(success_message)
        print_debug(success_message)
        print(f"SUCCESS: {success_message}")
        
        return True
        
    except Exception as e:
        error_msg = f"PDF generation failed: {str(e)}"
        logger.error(error_msg)
        logger.error(f"Full traceback: {traceback.format_exc()}")
        print_debug(f"ERROR: {error_msg}")
        raise

def main():
    """Main function"""
    logger = None
    try:
        print_debug("=== PDF Generation Script Started ===")
        
        # Set up logging
        logger = setup_logging()
        logger.info("=== PDF Generation Started ===")
        logger.info(f"Python version: {sys.version}")
        logger.info(f"Working directory: {os.getcwd()}")
        logger.info(f"Command line arguments: {sys.argv}")
        
        # Check if pdfkit is available
        try:
            import pdfkit
            logger.info(f"pdfkit module loaded successfully")
        except ImportError as e:
            raise ImportError(f"pdfkit module not found. Please install it with: pip install pdfkit. Error: {e}")
        
        # Validate arguments
        input_html, output_pdf, orientation = validate_arguments()
        
        # Generate PDF
        generate_pdf(input_html, output_pdf, orientation, logger)
        
        logger.info("=== PDF Generation Completed Successfully ===")
        print_debug("=== PDF Generation Completed Successfully ===")
        return 0
        
    except Exception as e:
        error_msg = f"FATAL ERROR: {str(e)}"
        if logger:
            logger.error(error_msg)
            logger.error(f"Full traceback: {traceback.format_exc()}")
        
        print_debug(error_msg)
        print(error_msg, file=sys.stderr)
        print(f"Traceback: {traceback.format_exc()}", file=sys.stderr)
        return 1

if __name__ == "__main__":
    exit_code = main()
    print_debug(f"Script exiting with code: {exit_code}")
    sys.exit(exit_code)