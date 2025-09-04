# Quotation Email Enhancement: Machine Technical Specifications

## Overview
Enhanced the quotation email functionality to automatically include machine technical specification PDFs as attachments when sending quotation emails.

## Changes Made

### 1. EmailService Class Enhancement (`email/email_service.php`)

#### New Method: `getMachineAttachmentsForQuotation()`
- **Purpose**: Retrieves machine attachment files for a specific quotation
- **Parameters**: `int $quotation_id` - The ID of the quotation
- **Returns**: `array` - Array of machine attachment details
- **Features**:
  - Queries database to find machines in quotation items
  - Only includes machines with attached technical specification files
  - Validates file existence before including
  - Generates safe filenames for email attachments
  - Includes error logging for debugging

#### Enhanced `sendQuotationEmail()` Method
- **New Features**:
  - Automatically fetches machine attachments for the quotation
  - Attaches technical specification PDFs to the email
  - Updates success message to indicate number of technical specifications included
  - Maintains backward compatibility with existing functionality

#### Enhanced `buildQuotationEmailBody()` Method
- **New Features**:
  - Includes a technical specifications section in the email body
  - Lists all machines with attached technical specifications
  - Visually highlights the technical specifications section
  - Automatically generated based on quotation content

### 2. Database Relationship
The enhancement leverages the existing database structure:

```sql
-- Quotation items reference machines
quotation_items:
- quotation_id (references quotations.id)
- item_type ('machine' or 'spare')
- item_id (references machines.id when item_type = 'machine')

-- Machines table contains attachment information
machines:
- id
- name, model, category
- attachment_filename
- attachment_path
- attachment_size
- attachment_type
```

## How It Works

1. **When sending a quotation email**:
   - System identifies all machine items in the quotation
   - For each machine, checks if it has an attached technical specification file
   - Validates that the file exists on the filesystem
   - Generates a safe filename for the email attachment
   - Attaches the PDF to the email

2. **Email Content Enhancement**:
   - Email body automatically includes a "Technical Specifications Included" section
   - Lists all machines that have technical specifications attached
   - Provides clear visual indication of additional attachments

3. **File Naming Convention**:
   - Technical specification files are renamed for email: `tech_spec_[MachineName]_[Model].pdf`
   - Special characters are replaced with underscores for safety
   - Original filename is preserved in database

## Usage Examples

### Example 1: Quotation with Machine Attachments
```
Quotation contains:
- Rapid Mixer Granulator (RMG-500) - has PDF attachment
- Octagonal Blender (OCT-2000L) - has PDF attachment
- Vibro Sifter (VS-48) - no attachment

Email will include:
- Main quotation PDF
- tech_spec_Rapid_Mixer_Granulator_RMG-500.pdf
- tech_spec_Octagonal_Blender_OCT-2000L.pdf
- Email body will list the machines with specifications
```

### Example 2: Quotation with No Machine Attachments
```
Quotation contains only spare parts or machines without attachments:
- Only main quotation PDF attached
- No technical specifications section in email body
- Normal email functionality maintained
```

## Benefits

1. **Automated Process**: No manual intervention required to attach technical specifications
2. **Comprehensive Information**: Customers receive complete technical details automatically
3. **Professional Presentation**: Clear indication of included technical specifications
4. **Backward Compatible**: Works with existing quotations and maintains all current functionality
5. **Error Handling**: Robust error handling prevents email failures due to missing files

## Files Modified

1. `/email/email_service.php` - Main email service enhancement
2. `/ajax/send_quotation_email.php` - Uses enhanced EmailService (automatic benefit)
3. `/quotations/send_quotation_email.php` - Uses enhanced EmailService (automatic benefit)

## Testing

A test file `test_machine_attachments.php` has been created to verify:
- Machine attachments are correctly identified
- File existence is validated
- Database queries work correctly
- EmailService method functions properly

## Error Handling

- File existence validation before attachment
- Database connection error handling
- Comprehensive error logging for debugging
- Graceful degradation if attachments can't be processed

## Future Enhancements

Potential future improvements:
1. Compression of multiple technical specification files into a single ZIP
2. Email size limits and handling for large attachments
3. Customer preference settings for technical specification inclusion
4. Technical specification version tracking and automatic updates
