# 🔧 Test Attendance Clock-In Fix

## Testing the updated attendance endpoint

$loginResponse = Invoke-RestMethod -Uri "http://localhost:8080/api/login" `
    -Method POST `
    -ContentType "application/json" `
    -Body '{"email":"manager@golden-fork.com","password":"password123"}'

Write-Host "✅ Login successful!"
$token = $loginResponse.access_token

# Test clock-in
$clockInResponse = Invoke-RestMethod -Uri "http://localhost:8080/api/staff/attendance/clock-in" `
    -Method POST `
    -ContentType "application/json" `
    -Headers @{"Authorization" = "Bearer $token"} `
    -Body '{"staff_id":10}'

Write-Host "✅ Clock-in successful!"
$clockInResponse | ConvertTo-Json -Depth 3