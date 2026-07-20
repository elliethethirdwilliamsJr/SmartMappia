# Driver Verification Setup

## Overview
This document outlines the setup process for the driver verification system where drivers upload documents that are stored on the VPS and require admin approval.

## Database Changes

### New Migration Files
1. `2026_07_20_000001_update_users_role_enum.php` - Updates user roles to support 'user', 'driver', 'restaurant'
2. `2026_07_20_000002_add_document_fields_to_drivers_table.php` - Adds document storage fields to drivers table

### Driver Table Fields Added
- `national_id_photo` - Path to uploaded national ID/Iqama
- `driving_license_photo` - Path to uploaded driving license
- `vehicle_registration_photo` - Path to uploaded vehicle registration (Istimara)
- `vehicle_insurance_photo` - Path to uploaded vehicle insurance
- `profile_photo` - Path to driver's profile photo
- `vehicle_plate_photo` - Path to vehicle photo with visible plate
- `verification_status` - ENUM: 'pending', 'approved', 'rejected'
- `rejection_reason` - TEXT: Reason for rejection (if applicable)
- `documents_submitted_at` - TIMESTAMP: When documents were submitted

## API Endpoints

### Driver Document Upload
```
POST /api/driver/upload-documents
Authorization: Bearer {token}
Content-Type: multipart/form-data

Required fields:
- national_id (image file, max 5MB)
- driving_license (image file, max 5MB)
- vehicle_registration (image file, max 5MB)
- vehicle_insurance (image file, max 5MB)
- profile_photo (image file, max 5MB)
- vehicle_photo (image file, max 5MB)
- vehicle_type (string: Sedan, SUV, Van, Motorcycle, Tricycle, Pickup Truck)
- vehicle_plate (string: e.g., ABC 1234)

Response:
{
  "success": true,
  "message": "Documents uploaded successfully. Awaiting admin approval.",
  "data": {
    "driver": { ... },
    "verification_status": "pending"
  }
}
```

### Get Verification Status
```
GET /api/driver/verification-status
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "has_submitted": true,
    "verification_status": "pending",
    "is_verified": false,
    "rejection_reason": null,
    "submitted_at": "2026-07-20T12:00:00Z",
    "verified_at": null
  }
}
```

### Get Driver Profile
```
GET /api/driver/profile
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": { ... driver profile ... }
}
```

## File Storage

### Storage Structure
Documents are stored in the VPS at:
```
storage/app/public/driver_documents/
├── national_ids/
├── driving_licenses/
├── vehicle_registrations/
├── vehicle_insurances/
├── profile_photos/
└── vehicle_photos/
```

### Setup Commands

Run these commands on your VPS:

```bash
# Navigate to backend directory
cd /path/to/backend

# Pull latest code
git pull

# Run migrations
php artisan migrate

# Create storage link (if not already created)
php artisan storage:link

# Set proper permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Create driver_documents folders
mkdir -p storage/app/public/driver_documents/{national_ids,driving_licenses,vehicle_registrations,vehicle_insurances,profile_photos,vehicle_photos}

# Set permissions for upload folders
chmod -R 775 storage/app/public/driver_documents
chown -R www-data:www-data storage/app/public/driver_documents

# Restart services
pm2 restart all
```

## Flutter Integration

### Registration Flow
1. User selects "Driver" account type (Step 1)
2. Fills in personal info + vehicle type & plate number (Step 2)
3. Verifies email (Step 3)
4. **NEW**: Uploads verification documents (Step 4)
5. Waits for admin approval

### Document Upload Screen
- Location: `lib/screens/driver_verification_screen.dart`
- Required documents: 6 (National ID, License, Registration, Insurance, Profile Photo, Vehicle Photo)
- Uses `image_picker` package to select images from gallery
- Shows upload status for each document
- Submit button only enabled when all 6 documents are uploaded

### After Upload
- Driver sees "Documents Submitted" success modal
- Driver can check verification status
- Driver cannot accept rides until `is_verified = true`
- Driver will see "Pending Verification" message in their dashboard

## Admin Approval Process (To Be Built)

### Admin Dashboard Features Needed
1. View pending driver verifications
2. View uploaded documents (images)
3. Approve or reject with reason
4. When approved:
   - Set `is_verified = true`
   - Set `verification_status = 'approved'`
   - Set `verified_at = now()`
5. When rejected:
   - Set `verification_status = 'rejected'`
   - Set `rejection_reason` (text explanation)
   - Driver can re-upload documents

## Security Notes

1. **File Validation**: Only image files (jpg, png, jpeg, pdf) up to 5MB
2. **Authentication**: All endpoints require Bearer token
3. **Role Check**: Only users with role='driver' can upload
4. **Storage**: Files stored in `/storage/app/public` (accessible via `/storage` URL)
5. **Gitignore**: Driver documents are excluded from git

## Testing

### Test Document Upload
```bash
# Use Postman or similar tool
POST http://your-vps-ip:8001/api/driver/upload-documents
Headers:
  Authorization: Bearer YOUR_TOKEN
  Content-Type: multipart/form-data

Body (form-data):
  national_id: [select image file]
  driving_license: [select image file]
  vehicle_registration: [select image file]
  vehicle_insurance: [select image file]
  profile_photo: [select image file]
  vehicle_photo: [select image file]
  vehicle_type: "Sedan"
  vehicle_plate: "ABC 1234"
```

### Verify Storage
```bash
# Check if files were created
ls -la storage/app/public/driver_documents/national_ids/
ls -la storage/app/public/driver_documents/driving_licenses/
# etc...

# Check if symlink exists
ls -la public/storage

# Access via browser (replace with your VPS IP)
http://your-vps-ip:8001/storage/driver_documents/national_ids/filename.jpg
```

## Next Steps

1. **Admin Dashboard**: Build UI to review and approve/reject drivers
2. **Email Notifications**: Send email when driver is approved/rejected
3. **Driver Dashboard**: Show verification status and pending message
4. **Document Re-upload**: Allow drivers to re-upload if rejected
5. **Document Expiry**: Track license/insurance expiry dates
