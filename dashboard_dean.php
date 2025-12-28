<?php
session_start();
include 'includes/db_connect.php';

// Check if user is logged in and is a dean
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'dean') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user'];

// Test function to verify data exists
function testStudentData($conn) {
    $test_query = "SELECT COUNT(*) as total FROM users WHERE role = 'student'";
    $result = $conn->query($test_query);
    if ($result) {
        $row = $result->fetch_assoc();
        error_log("Total students in database: " . $row['total']);
        
        // Get sample data
        $sample_query = "SELECT id, name, course, year_level, section FROM users WHERE role = 'student' LIMIT 5";
        $sample_result = $conn->query($sample_query);
        if ($sample_result && $sample_result->num_rows > 0) {
            error_log("Sample student data:");
            while ($student = $sample_result->fetch_assoc()) {
                error_log("  ID: {$student['id']}, Name: {$student['name']}, Course: {$student['course']}, Year: {$student['year_level']}, Section: {$student['section']}");
            }
        } else {
            error_log("No sample student data found");
        }
    } else {
        error_log("Test query failed: " . $conn->error);
    }
}

// Handle report generation
if (isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'] ?? 'both';
    $format = $_POST['format'] ?? 'csv';
    
    // Debug logging
    error_log("Generating report - Type: $report_type, Format: $format");
    
    // Test data before generating report
    error_log("=== DATA TEST BEFORE REPORT GENERATION ===");
    testStudentData($conn);
    error_log("=== END DATA TEST ===");
    
    if ($format === 'csv') {
        generateCSVReport($conn, $report_type);
        exit();
    } else {
        generatePDFReport($conn, $report_type);
        exit();
    }
}

// Function to generate CSV report
function generateCSVReport($conn, $report_type) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=dean_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    if ($report_type === 'teachers' || $report_type === 'both') {
        fputcsv($output, ['TEACHERS REPORT']);
        fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Teacher headers
        fputcsv($output, ['Teacher ID', 'Name', 'Email', 'Sections Assigned', 'Status', 'Last Activity']);
        
        // Get teacher data
        $teacher_query = "
            SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(s.id) as section_count,
                MAX(s.created_at) as last_activity
            FROM users u
            LEFT JOIN sections s ON u.id = s.teacher_id
            WHERE u.role = 'teacher'
            GROUP BY u.id, u.name, u.email
            ORDER BY section_count DESC, u.name ASC
        ";
        
        $teacher_result = $conn->query($teacher_query);
        if ($teacher_result) {
            while ($teacher = $teacher_result->fetch_assoc()) {
                $status = $teacher['section_count'] > 0 ? 'Active' : 'Inactive';
                $last_activity = $teacher['last_activity'] ? date('Y-m-d', strtotime($teacher['last_activity'])) : 'No activity';
                
                fputcsv($output, [
                    $teacher['id'],
                    $teacher['name'],
                    $teacher['email'],
                    $teacher['section_count'],
                    $status,
                    $last_activity
                ]);
            }
        }
        
        fputcsv($output, []);
        fputcsv($output, []);
    }
    
    if ($report_type === 'students' || $report_type === 'both') {
        fputcsv($output, ['STUDENTS REPORT']);
        fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Student headers
        fputcsv($output, ['Student ID', 'Name', 'Email', 'Course', 'Year Level', 'Section', 'Status', 'Enrollment Date']);
        
        // Get student data
        $student_query = "
            SELECT 
                u.id as student_id,
                u.name,
                u.email,
                u.course,
                u.year_level,
                u.section,
                u.created_at
            FROM users u
            WHERE u.role = 'student'
            ORDER BY u.course, u.year_level, u.section, u.name
        ";
        
        $student_result = $conn->query($student_query);
        if ($student_result) {
            while ($student = $student_result->fetch_assoc()) {
                $status = !empty($student['section']) ? 'Enrolled' : 'Unassigned';
                $enrollment_date = $student['created_at'] ? date('Y-m-d', strtotime($student['created_at'])) : 'N/A';
                
                fputcsv($output, [
                    $student['student_id'] ?? 'N/A',
                    $student['name'],
                    $student['email'],
                    $student['course'] ?? 'N/A',
                    $student['year_level'] ?? 'N/A',
                    $student['section'] ?? 'No Section',
                    $status,
                    $enrollment_date
                ]);
            }
        }
        
        fputcsv($output, []);
        
        // Summary statistics
        fputcsv($output, ['SUMMARY STATISTICS']);
        fputcsv($output, []);
        
        // Get summary data
        $summary_query = "
            SELECT 
                (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
                (SELECT COUNT(*) FROM users WHERE role = 'teacher') as total_teachers,
                (SELECT COUNT(*) FROM users WHERE role = 'program_chair') as total_program_chairs,
                (SELECT COUNT(*) FROM sections) as total_sections,
                (SELECT COUNT(DISTINCT course) FROM sections) as total_programs,
                (SELECT COUNT(DISTINCT teacher_id) FROM sections WHERE teacher_id IS NOT NULL) as active_teachers,
                (SELECT COUNT(*) FROM users u WHERE u.role = 'student' AND NOT EXISTS (
                    SELECT 1 FROM sections s WHERE s.course = u.course AND s.year_level = u.year_level AND s.section = u.section
                )) as unassigned_students,
                (SELECT COUNT(*) FROM sections WHERE teacher_id IS NULL) as unassigned_sections
        ";
        
        $summary_result = $conn->query($summary_query);
        if ($summary_result && $summary = $summary_result->fetch_assoc()) {
            $teacher_activity_rate = $summary['total_teachers'] > 0 ? round(($summary['active_teachers'] / $summary['total_teachers']) * 100) : 0;
            $student_enrollment_rate = $summary['total_students'] > 0 ? round((($summary['total_students'] - $summary['unassigned_students']) / $summary['total_students']) * 100) : 0;
            
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Total Students', $summary['total_students']]);
            fputcsv($output, ['Total Teachers', $summary['total_teachers']]);
            fputcsv($output, ['Total Program Chairs', $summary['total_program_chairs']]);
            fputcsv($output, ['Active Sections', $summary['total_sections']]);
            fputcsv($output, ['Active Programs', $summary['total_programs']]);
            fputcsv($output, ['Active Teachers', $summary['active_teachers']]);
            fputcsv($output, ['Teacher Activity Rate', $teacher_activity_rate . '%']);
            fputcsv($output, ['Unassigned Students', $summary['unassigned_students']]);
            fputcsv($output, ['Student Enrollment Rate', $student_enrollment_rate . '%']);
            fputcsv($output, ['Unassigned Sections', $summary['unassigned_sections']]);
        }
    }
    
    fclose($output);
    exit();
}

// Function to generate PDF report (requires TCPDF library)
function generatePDFReport($conn, $report_type) {
    // First check if there are students in the database
    error_log("=== STARTING PDF REPORT GENERATION ===");
    error_log("Report type: $report_type");
    
    $test_query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
    $test_result = $conn->query($test_query);
    
    if (!$test_result) {
        error_log("Database error in test query: " . $conn->error);
        // Fall back to CSV
        error_log("Falling back to CSV due to database error");
        generateCSVReport($conn, $report_type);
        return;
    }
    
    $test_data = $test_result->fetch_assoc();
    $student_count = $test_data['count'];
    error_log("Found $student_count students in database");
    
    // Check if TCPDF is installed
    $tcpdf_path = __DIR__ . '/tcpdf/tcpdf.php';
    if (!file_exists($tcpdf_path)) {
        // Fallback to CSV if TCPDF not available
        error_log("TCPDF not found at: $tcpdf_path, falling back to CSV");
        generateCSVReport($conn, $report_type);
        return;
    }
    
    require_once($tcpdf_path);
    
    // Create new PDF document
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('PLMUN LMS');
    $pdf->SetAuthor('Dean Dashboard');
    $pdf->SetTitle('PLMUN LMS Report');
    $pdf->SetSubject('Activity Report');
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Title with PLMUN logo and header
    $html = '
    <style>
        .header { 
            border-bottom: 2px solid #4F46E5; 
            padding-bottom: 10px; 
            margin-bottom: 15px;
        }
        .title { 
            color: #1F2937; 
            font-size: 24px; 
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        .subtitle { 
            color: #6B7280; 
            font-size: 12px; 
            text-align: center;
            margin-bottom: 15px;
        }
        .section-title { 
            color: #4F46E5; 
            font-size: 16px; 
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #E5E7EB;
        }
        .table-header {
            background-color: #4F46E5;
            color: white;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #E5E7EB;
        }
        .table-cell {
            padding: 8px;
            border: 1px solid #E5E7EB;
            font-size: 10px;
        }
        .active-status {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .inactive-status {
            background-color: #FEE2E2;
            color: #991B1B;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .enrolled-status {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .unassigned-status {
            background-color: #FEF3C7;
            color: #92400E;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }
        .summary-box {
            background-color: #F8FAFC;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .summary-title {
            color: #4F46E5;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .summary-label {
            color: #6B7280;
            font-size: 11px;
        }
        .summary-value {
            color: #1F2937;
            font-weight: bold;
            font-size: 11px;
        }
        .highlight-green {
            color: #059669;
            font-weight: bold;
        }
        .highlight-red {
            color: #DC2626;
            font-weight: bold;
        }
        .highlight-yellow {
            color: #D97706;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            color: #9CA3AF;
            font-size: 10px;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #E5E7EB;
        }
    </style>
    
    <div class="header">
        <div class="title">PLMUN LEARNING MANAGEMENT SYSTEM</div>
        <div class="subtitle">Dean Dashboard Report - Generated on ' . date('F d, Y h:i A') . '</div>
    </div>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Add debug logging
    error_log("Generating PDF report - Type: $report_type");
    
    if ($report_type === 'teachers' || $report_type === 'both') {
        // Teachers section
        $html = '<div class="section-title">📊 TEACHERS ACTIVITY REPORT</div>';
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Get teacher data
        $teacher_query = "
            SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(s.id) as section_count,
                MAX(s.created_at) as last_activity
            FROM users u
            LEFT JOIN sections s ON u.id = s.teacher_id
            WHERE u.role = 'teacher'
            GROUP BY u.id, u.name, u.email
            ORDER BY section_count DESC, u.name ASC
        ";
        
        error_log("Teacher query: " . $teacher_query);
        $teacher_result = $conn->query($teacher_query);
        
        if ($teacher_result && $teacher_result->num_rows > 0) {
            error_log("Found " . $teacher_result->num_rows . " teachers");
            // Create table header
            $html = '
            <table cellpadding="5" cellspacing="0" width="100%">
                <tr>
                    <td class="table-header" width="10%"><b>ID</b></td>
                    <td class="table-header" width="30%"><b>Teacher Name</b></td>
                    <td class="table-header" width="30%"><b>Email</b></td>
                    <td class="table-header" width="10%" align="center"><b>Sections</b></td>
                    <td class="table-header" width="10%" align="center"><b>Status</b></td>
                    <td class="table-header" width="20%"><b>Last Activity</b></td>
                </tr>';
            
            while ($teacher = $teacher_result->fetch_assoc()) {
                $status = $teacher['section_count'] > 0 ? 'Active' : 'Inactive';
                $status_class = $teacher['section_count'] > 0 ? 'active-status' : 'inactive-status';
                $last_activity = $teacher['last_activity'] ? date('M d, Y', strtotime($teacher['last_activity'])) : 'No activity';
                
                $html .= '
                <tr>
                    <td class="table-cell">' . $teacher['id'] . '</td>
                    <td class="table-cell">' . htmlspecialchars($teacher['name']) . '</td>
                    <td class="table-cell">' . htmlspecialchars($teacher['email']) . '</td>
                    <td class="table-cell" align="center">' . $teacher['section_count'] . '</td>
                    <td class="table-cell" align="center"><span class="' . $status_class . '">' . $status . '</span></td>
                    <td class="table-cell">' . $last_activity . '</td>
                </tr>';
            }
            
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Teachers summary
            $summary_query = "
                SELECT 
                    COUNT(*) as total_teachers,
                    SUM(CASE WHEN EXISTS (SELECT 1 FROM sections WHERE teacher_id = u.id) THEN 1 ELSE 0 END) as active_teachers,
                    AVG(CASE WHEN EXISTS (SELECT 1 FROM sections WHERE teacher_id = u.id) THEN 1 ELSE 0 END) * 100 as activity_rate
                FROM users u
                WHERE u.role = 'teacher'
            ";
            
            $summary_result = $conn->query($summary_query);
            if ($summary_result && $summary = $summary_result->fetch_assoc()) {
                $activity_rate = round($summary['activity_rate'], 1);
                $inactive_teachers = $summary['total_teachers'] - $summary['active_teachers'];
                
                $html = '
                <div class="summary-box">
                    <div class="summary-title">📈 TEACHERS SUMMARY</div>
                    <div class="summary-row">
                        <span class="summary-label">Total Teachers:</span>
                        <span class="summary-value">' . $summary['total_teachers'] . '</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Active Teachers:</span>
                        <span class="summary-value highlight-green">' . $summary['active_teachers'] . '</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Inactive Teachers:</span>
                        <span class="summary-value highlight-red">' . $inactive_teachers . '</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Activity Rate:</span>
                        <span class="summary-value ' . ($activity_rate >= 80 ? 'highlight-green' : ($activity_rate >= 50 ? 'highlight-yellow' : 'highlight-red')) . '">' . $activity_rate . '%</span>
                    </div>
                </div>';
                
                $pdf->writeHTML($html, true, false, true, false, '');
            }
        } else {
            $html = '<p style="color: #6B7280; text-align: center; padding: 20px;">No teacher data available</p>';
            $pdf->writeHTML($html, true, false, true, false, '');
            error_log("No teacher data found or query failed");
        }
    }
    
    if ($report_type === 'students' || $report_type === 'both') {
        // Add new page for students section if combined report
        if ($report_type === 'both') {
            $pdf->AddPage();
        }
        
        // Students section
        $html = '<div class="section-title">🎓 STUDENTS ENROLLMENT REPORT</div>';
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Check if there are students
        if ($student_count == 0) {
            $html = '<div style="text-align: center; padding: 40px;">
                <p style="color: #DC2626; font-size: 16px; font-weight: bold; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> No Student Data Available
                </p>
                <p style="color: #6B7280; margin-bottom: 10px;">There are no students registered in the system.</p>
                <p style="color: #6B7280; font-size: 12px;">Please check the users table for student records.</p>
            </div>';
            $pdf->writeHTML($html, true, false, true, false, '');
            error_log("No students found in database, showing empty state");
        } else {
            // Get student data
            $student_query = "
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.course,
                    u.year_level,
                    u.section,
                    u.created_at
                FROM users u
                WHERE u.role = 'student'
                ORDER BY u.course, u.year_level, u.section, u.name
                LIMIT 100
            ";
            
            error_log("Student query: " . $student_query);
            $student_result = $conn->query($student_query);
            
            if ($student_result === false) {
                // Query failed
                error_log("Student query failed: " . $conn->error);
                $html = '<p style="color: #DC2626; text-align: center; padding: 20px; font-weight: bold;">
                    <i class="fas fa-exclamation-circle"></i> Error: Could not retrieve student data
                </p>
                <p style="color: #6B7280; text-align: center; font-size: 12px;">Database error: ' . htmlspecialchars($conn->error) . '</p>';
                $pdf->writeHTML($html, true, false, true, false, '');
            } else {
                error_log("Student query executed successfully");
                error_log("Number of rows: " . $student_result->num_rows);
                
                if ($student_result->num_rows > 0) {
                    // Create table header
                    $html = '
                    <table cellpadding="5" cellspacing="0" width="100%">
                        <tr>
                            <td class="table-header" width="10%"><b>ID</b></td>
                            <td class="table-header" width="30%"><b>Student Name</b></td>
                            <td class="table-header" width="15%"><b>Course</b></td>
                            <td class="table-header" width="10%" align="center"><b>Year</b></td>
                            <td class="table-header" width="15%"><b>Section</b></td>
                            <td class="table-header" width="10%" align="center"><b>Status</b></td>
                            <td class="table-header" width="20%"><b>Enrolled Date</b></td>
                        </tr>';
                    
                    $counter = 0;
                    while ($student = $student_result->fetch_assoc()) {
                        $counter++;
                        error_log("Processing student #$counter: " . ($student['name'] ?? 'N/A'));
                        
                        $status = !empty($student['section']) ? 'Enrolled' : 'Unassigned';
                        $status_class = !empty($student['section']) ? 'enrolled-status' : 'unassigned-status';
                        $enrollment_date = $student['created_at'] ? date('M d, Y', strtotime($student['created_at'])) : 'N/A';
                        
                        $html .= '
                        <tr>
                            <td class="table-cell">' . ($student['id'] ?? 'N/A') . '</td>
                            <td class="table-cell">' . htmlspecialchars($student['name'] ?? 'N/A') . '</td>
                            <td class="table-cell">' . htmlspecialchars($student['course'] ?? 'N/A') . '</td>
                            <td class="table-cell" align="center">' . ($student['year_level'] ?? 'N/A') . '</td>
                            <td class="table-cell">' . htmlspecialchars($student['section'] ?? 'No Section') . '</td>
                            <td class="table-cell" align="center"><span class="' . $status_class . '">' . $status . '</span></td>
                            <td class="table-cell">' . $enrollment_date . '</td>
                        </tr>';
                    }
                    
                    $html .= '</table>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                    
                    // Add note about data
                    $html = '<p style="color: #6B7280; font-size: 9px; text-align: center; margin-top: 10px;">Showing ' . $counter . ' students (first 100 of ' . $student_count . ' total)</p>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                    
                    // Add comprehensive summary
                    $summary_query = "
                        SELECT 
                            (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
                            (SELECT COUNT(*) FROM users WHERE role = 'teacher') as total_teachers,
                            (SELECT COUNT(*) FROM users WHERE role = 'program_chair') as total_program_chairs,
                            (SELECT COUNT(*) FROM sections) as total_sections,
                            (SELECT COUNT(DISTINCT course) FROM sections) as total_programs,
                            (SELECT COUNT(DISTINCT teacher_id) FROM sections WHERE teacher_id IS NOT NULL) as active_teachers,
                            (SELECT COUNT(*) FROM users u WHERE u.role = 'student' AND NOT EXISTS (
                                SELECT 1 FROM sections s WHERE s.course = u.course 
                                AND s.year_level = u.year_level 
                                AND s.section = u.section
                            )) as unassigned_students,
                            (SELECT COUNT(*) FROM sections WHERE teacher_id IS NULL) as unassigned_sections,
                            (SELECT COUNT(DISTINCT course) FROM users WHERE role = 'student' AND course IS NOT NULL) as total_courses
                    ";
                    
                    $summary_result = $conn->query($summary_query);
                    if ($summary_result && $summary = $summary_result->fetch_assoc()) {
                        $teacher_activity_rate = $summary['total_teachers'] > 0 ? round(($summary['active_teachers'] / $summary['total_teachers']) * 100) : 0;
                        $student_enrollment_rate = $summary['total_students'] > 0 ? round((($summary['total_students'] - $summary['unassigned_students']) / $summary['total_students']) * 100) : 0;
                        
                        $html = '
                        <div class="summary-box">
                            <div class="summary-title">📊 SYSTEM OVERVIEW</div>
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                <div style="width: 48%;">
                                    <div class="summary-title" style="font-size: 12px; margin-bottom: 8px;">👥 STUDENTS</div>
                                    <div class="summary-row">
                                        <span class="summary-label">Total Students:</span>
                                        <span class="summary-value">' . number_format($summary['total_students']) . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Unassigned Students:</span>
                                        <span class="summary-value highlight-red">' . $summary['unassigned_students'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Enrollment Rate:</span>
                                        <span class="summary-value ' . ($student_enrollment_rate >= 90 ? 'highlight-green' : ($student_enrollment_rate >= 70 ? 'highlight-yellow' : 'highlight-red')) . '">' . $student_enrollment_rate . '%</span>
                                    </div>
                                </div>
                                
                                <div style="width: 48%;">
                                    <div class="summary-title" style="font-size: 12px; margin-bottom: 8px;">👨‍🏫 FACULTY</div>
                                    <div class="summary-row">
                                        <span class="summary-label">Total Teachers:</span>
                                        <span class="summary-value">' . $summary['total_teachers'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Active Teachers:</span>
                                        <span class="summary-value highlight-green">' . $summary['active_teachers'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Activity Rate:</span>
                                        <span class="summary-value ' . ($teacher_activity_rate >= 80 ? 'highlight-green' : ($teacher_activity_rate >= 50 ? 'highlight-yellow' : 'highlight-red')) . '">' . $teacher_activity_rate . '%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between;">
                                <div style="width: 48%;">
                                    <div class="summary-title" style="font-size: 12px; margin-bottom: 8px;">📚 ACADEMICS</div>
                                    <div class="summary-row">
                                        <span class="summary-label">Programs/Courses:</span>
                                        <span class="summary-value">' . $summary['total_programs'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Active Sections:</span>
                                        <span class="summary-value">' . $summary['total_sections'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Unassigned Sections:</span>
                                        <span class="summary-value highlight-yellow">' . $summary['unassigned_sections'] . '</span>
                                    </div>
                                </div>
                                
                                <div style="width: 48%;">
                                    <div class="summary-title" style="font-size: 12px; margin-bottom: 8px;">👥 ADMINISTRATION</div>
                                    <div class="summary-row">
                                        <span class="summary-label">Program Chairs:</span>
                                        <span class="summary-value">' . $summary['total_program_chairs'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Total Courses:</span>
                                        <span class="summary-value">' . $summary['total_courses'] . '</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Report Date:</span>
                                        <span class="summary-value">' . date('M d, Y') . '</span>
                                    </div>
                                </div>
                            </div>
                        </div>';
                        
                        $pdf->writeHTML($html, true, false, true, false, '');
                    }
                } else {
                    $html = '<p style="color: #6B7280; text-align: center; padding: 20px;">No student records found in the database. Please check if students are properly registered.</p>';
                    $html .= '<p style="color: #6B7280; text-align: center; font-size: 10px;">Query returned 0 rows but database says there are ' . $student_count . ' students.</p>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                    error_log("No student rows found in database");
                }
            }
        }
    }
    
    // Add footer
    $html = '
    <div class="footer">
        <p>Generated by PLMUN LMS Dean Dashboard • Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages() . '</p>
        <p>This is an official system-generated report</p>
    </div>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Output PDF - Force download with proper filename
    $filename = 'PLMUN_Dean_Report_' . date('Y-m-d_H-i') . '.pdf';
    error_log("PDF generated successfully, outputting file: $filename");
    $pdf->Output($filename, 'D');
    exit();
}

// Rest of your existing code for the dashboard...
// Get overview statistics with activity metrics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
        (SELECT COUNT(*) FROM users WHERE role = 'teacher') as total_teachers,
        (SELECT COUNT(*) FROM users WHERE role = 'program_chair') as total_program_chairs,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') as total_staff,
        (SELECT COUNT(*) FROM sections) as total_sections,
        (SELECT COUNT(DISTINCT course) FROM sections) as total_programs
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total_students' => 0, 'total_teachers' => 0, 'total_program_chairs' => 0, 'total_staff' => 0, 'total_sections' => 0, 'total_programs' => 0];

// Get active teachers (teachers with sections)
$active_teachers_query = "
    SELECT COUNT(DISTINCT teacher_id) as count 
    FROM sections 
    WHERE teacher_id IS NOT NULL
";
$active_teachers_result = $conn->query($active_teachers_query);
$active_teachers = $active_teachers_result ? ($active_teachers_result->fetch_assoc()['count'] ?? 0) : 0;

// Get inactive teachers (teachers without sections)
$inactive_teachers = max(0, ($stats['total_teachers'] - $active_teachers));

// Get teacher activity details
$teacher_activity_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        COUNT(s.id) as section_count,
        MAX(s.created_at) as last_activity
    FROM users u
    LEFT JOIN sections s ON u.id = s.teacher_id
    WHERE u.role = 'teacher'
    GROUP BY u.id, u.name, u.email
    ORDER BY section_count DESC, u.name ASC
";
$teacher_activity_result = $conn->query($teacher_activity_query);
$teacher_activity = [];
if ($teacher_activity_result) {
    if (method_exists($teacher_activity_result, 'fetch_all')) {
        $teacher_activity = $teacher_activity_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $teacher_activity_result->fetch_assoc()) {
            $teacher_activity[] = $row;
        }
    }
}

// Get students with no sections (not enrolled properly)
$unassigned_students_query = "
    SELECT COUNT(*) as count 
    FROM users u
    WHERE u.role = 'student' 
    AND NOT EXISTS (
        SELECT 1 FROM sections s 
        WHERE s.course = u.course 
        AND s.year_level = u.year_level 
        AND s.section = u.section
    )
";
$unassigned_students_result = $conn->query($unassigned_students_query);
$unassigned_students = $unassigned_students_result ? ($unassigned_students_result->fetch_assoc()['count'] ?? 0) : 0;

// Get program chair activity
$program_chair_activity_query = "
    SELECT 
        u.name,
        u.email,
        u.department,
        u.created_at
    FROM users u
    WHERE u.role = 'program_chair'
    ORDER BY u.name
";
$program_chairs_result = $conn->query($program_chair_activity_query);
$program_chairs = [];
if ($program_chairs_result) {
    if (method_exists($program_chairs_result, 'fetch_all')) {
        $program_chairs = $program_chairs_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $program_chairs_result->fetch_assoc()) {
            $program_chairs[] = $row;
        }
    }
}

// Get student enrollment by program
$student_distribution_query = "
    SELECT 
        course,
        COUNT(*) as student_count,
        COUNT(DISTINCT year_level) as year_levels
    FROM users
    WHERE role = 'student'
    GROUP BY course
    ORDER BY course
";
$student_distribution_result = $conn->query($student_distribution_query);
$student_distribution = [];
if ($student_distribution_result) {
    if (method_exists($student_distribution_result, 'fetch_all')) {
        $student_distribution = $student_distribution_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $student_distribution_result->fetch_assoc()) {
            $student_distribution[] = $row;
        }
    }
}

// Get recent system activity
$recent_activity_query = "
    SELECT 
        'Section Created' as activity_type,
        s.section_code as details,
        u.name as performed_by,
        s.created_at as activity_time
    FROM sections s
    LEFT JOIN users u ON s.teacher_id = u.id
    ORDER BY s.created_at DESC
    LIMIT 10
";
$recent_activities_result = $conn->query($recent_activity_query);
$recent_activities = [];
if ($recent_activities_result) {
    if (method_exists($recent_activities_result, 'fetch_all')) {
        $recent_activities = $recent_activities_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $recent_activities_result->fetch_assoc()) {
            $recent_activities[] = $row;
        }
    }
}

// Get sections without teachers
$unassigned_sections_query = "
    SELECT COUNT(*) as count 
    FROM sections 
    WHERE teacher_id IS NULL
";
$unassigned_sections_result = $conn->query($unassigned_sections_query);
$unassigned_sections = $unassigned_sections_result ? ($unassigned_sections_result->fetch_assoc()['count'] ?? 0) : 0;

// Get all students with section information for the modal
$all_students_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.course,
        u.year_level,
        u.section,
        u.created_at
    FROM users u
    WHERE u.role = 'student'
    ORDER BY u.course, u.year_level, u.section, u.name
";

$all_students_result = $conn->query($all_students_query);
$all_students = [];
if ($all_students_result) {
    if (method_exists($all_students_result, 'fetch_all')) {
        $all_students = $all_students_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $all_students_result->fetch_assoc()) {
            $all_students[] = $row;
        }
    }
}

// Get courses for filter
$courses_query = "SELECT DISTINCT course FROM users WHERE role = 'student' AND course IS NOT NULL AND course != '' ORDER BY course";
$courses_result = $conn->query($courses_query);
$courses = [];
if ($courses_result) {
    if (method_exists($courses_result, 'fetch_all')) {
        $courses = $courses_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $courses_result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
}

// Get year levels for filter
$year_levels_query = "SELECT DISTINCT year_level FROM users WHERE role = 'student' AND year_level IS NOT NULL AND year_level != '' ORDER BY CAST(year_level AS UNSIGNED)";
$year_levels_result = $conn->query($year_levels_query);
$year_levels = [];
if ($year_levels_result) {
    if (method_exists($year_levels_result, 'fetch_all')) {
        $year_levels = $year_levels_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $year_levels_result->fetch_assoc()) {
            $year_levels[] = $row;
        }
    }
}

// Get sections for filter
$sections_query = "SELECT DISTINCT section FROM users WHERE role = 'student' AND section IS NOT NULL AND section != '' ORDER BY section";
$sections_result = $conn->query($sections_query);
$sections = [];
if ($sections_result) {
    if (method_exists($sections_result, 'fetch_all')) {
        $sections = $sections_result->fetch_all(MYSQLI_ASSOC);
    } else {
        while ($row = $sections_result->fetch_assoc()) {
            $sections[] = $row;
        }
    }
}

// Calculate activity rates
$teacher_activity_rate = $stats['total_teachers'] > 0 ? round(($active_teachers / $stats['total_teachers']) * 100) : 0;
$student_enrollment_rate = $stats['total_students'] > 0 ? round((($stats['total_students'] - $unassigned_students) / $stats['total_students']) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dean Dashboard | PLMUN LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .teacher-row {
            transition: all 0.3s ease;
        }
        .teacher-row.hidden {
            display: none;
        }
        .student-row {
            transition: all 0.2s ease;
        }
        .student-row:hover {
            background-color: #f9fafb;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 95%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .close-modal {
            color: #6b7280;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            padding: 0 10px;
        }
        .close-modal:hover {
            color: #374151;
        }
        
        /* Fix for reports modal layout */
        #reports-modal .modal-content {
            max-height: 90vh !important;
            overflow-y: auto !important;
        }

        /* Make sure content fits */
        #reports-modal .p-6 {
            padding: 1.5rem !important;
        }

        /* Better spacing for radio button groups */
        #reports-modal .grid {
            gap: 0.75rem !important;
        }

        /* Make radio button cards smaller */
        #reports-modal .p-4 {
            padding: 1rem !important;
        }

        /* Ensure generate button is always visible */
        #reports-modal .sticky-buttons {
            padding-top: 1.5rem !important;
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            margin-top: auto;
        }

        /* Custom scrollbar for modals */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .modal-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #reports-modal .modal-content {
                margin: 1% auto !important;
                width: 98% !important;
            }
            
            #reports-modal .grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            
            #reports-modal .grid-cols-2 {
                grid-template-columns: 1fr !important;
            }
            
            .modal-content {
                width: 98% !important;
                margin: 1% auto !important;
            }
        }

        /* Make modal header sticky */
        .modal-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background: inherit;
        }

        /* Radio button fixes */
        .report-option, .format-option {
            position: relative;
        }

        .report-card, .format-card {
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            height: 100%;
        }

        .report-card:hover, .format-card:hover {
            border-color: #10b981;
            background-color: #f9fafb;
        }

        /* Selected state */
        .report-card.selected, .format-card.selected {
            border-color: #10b981 !important;
            background-color: #f0fdf4 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
        }

        /* PDF format specific styling */
        .format-card[data-value="pdf"] .pdf-features {
            display: block;
        }

        .pdf-features {
            display: none;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #d1d5db;
        }

        .pdf-feature {
            font-size: 0.7rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
        }

        .pdf-feature i {
            margin-right: 0.25rem;
            font-size: 0.6rem;
        }

        /* Report preview enhancements */
        .report-preview-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .report-preview-item:last-child {
            border-bottom: none;
        }

        /* Format indicator */
        .format-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .format-indicator.csv {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .format-indicator.pdf {
            background-color: #fce7f3;
            color: #be185d;
        }

        /* TCPDF status indicator */
        .tcpdf-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            background-color: #dcfce7;
            color: #166534;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .tcpdf-status i {
            margin-right: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-lg shadow-lg p-8 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Welcome, Dean <?php echo htmlspecialchars($user_name); ?>! 🎓</h1>
                    <p class="text-purple-100">Monitoring Activity & Engagement Across CITCS And More</p>
                </div>
                <div class="text-right">
                    <div class="text-5xl mb-2">📊</div>
                    <p class="text-sm text-purple-200"><?php echo date('l, F d, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Activity Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Teachers Activity -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-chalkboard-teacher text-green-600 text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Activity Rate</div>
                        <div class="text-lg font-bold <?php echo $teacher_activity_rate >= 80 ? 'text-green-600' : ($teacher_activity_rate >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                            <?php echo $teacher_activity_rate; ?>%
                        </div>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $active_teachers; ?>/<?php echo $stats['total_teachers']; ?></p>
                <p class="text-sm text-gray-600 mt-1">Active Teachers</p>
                <?php if ($inactive_teachers > 0): ?>
                <div class="mt-3 flex items-center text-orange-600 text-sm">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <span><?php echo $inactive_teachers; ?> inactive</span>
                </div>
                <?php else: ?>
                <div class="mt-3 flex items-center text-green-600 text-sm">
                    <i class="fas fa-check-circle mr-1"></i>
                    <span>All active</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Students Enrollment -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-user-graduate text-blue-600 text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Enrolled</div>
                        <div class="text-lg font-bold <?php echo $student_enrollment_rate >= 90 ? 'text-green-600' : ($student_enrollment_rate >= 70 ? 'text-yellow-600' : 'text-red-600'); ?>">
                            <?php echo $student_enrollment_rate; ?>%
                        </div>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_students']); ?></p>
                <p class="text-sm text-gray-600 mt-1">Total Students</p>
                <?php if ($unassigned_students > 0): ?>
                <div class="mt-3 flex items-center text-orange-600 text-sm">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <span><?php echo $unassigned_students; ?> unassigned</span>
                </div>
                <?php else: ?>
                <div class="mt-3 flex items-center text-green-600 text-sm">
                    <i class="fas fa-check-circle mr-1"></i>
                    <span>All enrolled</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Program Chairs -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-user-tie text-indigo-600 text-2xl"></i>
                    </div>
                    <span class="text-xs text-gray-500">Admins</span>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_program_chairs']; ?></p>
                <p class="text-sm text-gray-600 mt-1">Program Chairs</p>
                <div class="mt-3 flex items-center text-indigo-600 text-sm">
                    <i class="fas fa-users mr-1"></i>
                    <span>Managing <?php echo $stats['total_programs']; ?> programs</span>
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-heartbeat text-purple-600 text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Status</div>
                        <div class="text-lg font-bold text-green-600">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_sections']; ?></p>
                <p class="text-sm text-gray-600 mt-1">Active Sections</p>
                <?php if ($unassigned_sections > 0): ?>
                <div class="mt-3 flex items-center text-orange-600 text-sm">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <span><?php echo $unassigned_sections; ?> no teacher</span>
                </div>
                <?php else: ?>
                <div class="mt-3 flex items-center text-green-600 text-sm">
                    <i class="fas fa-check-circle mr-1"></i>
                    <span>All assigned</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alerts Section -->
        <?php if ($inactive_teachers > 0 || $unassigned_students > 0 || $unassigned_sections > 0): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-8">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-lg font-bold text-yellow-800">Action Required</h3>
                    <div class="mt-2 text-sm text-yellow-700 space-y-1">
                        <?php if ($inactive_teachers > 0): ?>
                        <p>• <strong><?php echo $inactive_teachers; ?> teacher(s)</strong> have no sections assigned</p>
                        <?php endif; ?>
                        <?php if ($unassigned_students > 0): ?>
                        <p>• <strong><?php echo $unassigned_students; ?> student(s)</strong> not properly enrolled in sections</p>
                        <?php endif; ?>
                        <?php if ($unassigned_sections > 0): ?>
                        <p>• <strong><?php echo $unassigned_sections; ?> section(s)</strong> have no teacher assigned</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Teacher Activity Monitor -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-user-check text-green-500 mr-2"></i>Teacher Activity Monitor
                        </h2>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-500">
                                <span id="active-count"><?php echo $active_teachers; ?></span> active, 
                                <span id="inactive-count"><?php echo $inactive_teachers; ?></span> inactive
                            </span>
                            <!-- Status Filter Dropdown -->
                            <div class="relative">
                                <select id="status-filter" class="appearance-none bg-gray-100 border border-gray-300 text-gray-700 py-2 px-4 pr-8 rounded-lg leading-tight focus:outline-none focus:bg-white focus:border-blue-500">
                                    <option value="all">All Status</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($teacher_activity)): ?>
                            <p class="text-gray-500 text-center py-8">No teachers in system</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Teacher</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Sections</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Last Activity</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-600">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200" id="teacher-table-body">
                                        <?php 
                                        $active_count = 0;
                                        $inactive_count = 0;
                                        foreach ($teacher_activity as $teacher): 
                                            $is_active = $teacher['section_count'] > 0;
                                            $status_class = $is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                            $status_text = $is_active ? 'Active' : 'Inactive';
                                            $status_icon = $is_active ? 'fa-check-circle' : 'fa-exclamation-circle';
                                            $status_value = $is_active ? 'active' : 'inactive';
                                            
                                            if ($is_active) $active_count++;
                                            else $inactive_count++;
                                        ?>
                                        <tr class="hover:bg-gray-50 teacher-row" data-status="<?php echo $status_value; ?>">
                                            <td class="px-4 py-3">
                                                <div>
                                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($teacher['email']); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                                    <?php echo $teacher['section_count']; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-600">
                                                <?php echo $teacher['last_activity'] ? date('M d, Y', strtotime($teacher['last_activity'])) : 'No activity'; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-3 py-1 <?php echo $status_class; ?> rounded-full text-xs font-semibold">
                                                    <i class="fas <?php echo $status_icon; ?> mr-1"></i><?php echo $status_text; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Student Distribution by Program -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-chart-pie text-blue-500 mr-2"></i>Student Distribution by Program
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($student_distribution)): ?>
                            <p class="text-gray-500 text-center py-8">No student data</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php 
                                $total_students = array_sum(array_column($student_distribution, 'student_count'));
                                $program_colors = [
                                    'BSCS' => ['bg' => 'bg-blue-500', 'light' => 'bg-blue-50', 'border' => 'border-blue-200'],
                                    'BSIT' => ['bg' => 'bg-green-500', 'light' => 'bg-green-50', 'border' => 'border-green-200'],
                                    'ACT' => ['bg' => 'bg-purple-500', 'light' => 'bg-purple-50', 'border' => 'border-purple-200']
                                ];
                                
                                foreach ($student_distribution as $program): 
                                    $colors = $program_colors[$program['course']] ?? ['bg' => 'bg-gray-500', 'light' => 'bg-gray-50', 'border' => 'border-gray-200'];
                                    $percentage = $total_students > 0 ? round(($program['student_count'] / $total_students) * 100) : 0;
                                ?>
                                    <div class="border rounded-lg p-4 <?php echo $colors['light']; ?> <?php echo $colors['border']; ?>">
                                        <div class="flex items-center justify-between mb-3">
                                            <div>
                                                <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($program['course']); ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo $program['year_levels']; ?> year level(s)</p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-3xl font-bold text-gray-800"><?php echo $program['student_count']; ?></div>
                                                <div class="text-xs text-gray-600">students</div>
                                            </div>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-3">
                                            <div class="<?php echo $colors['bg']; ?> h-3 rounded-full flex items-center justify-end pr-2" style="width: <?php echo $percentage; ?>%">
                                                <span class="text-white text-xs font-bold"><?php echo $percentage; ?>%</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent System Activity -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-history text-purple-500 mr-2"></i>Recent System Activity
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recent_activities)): ?>
                            <p class="text-gray-500 text-center py-8">No recent activity</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="flex items-start space-x-3 pb-3 border-b last:border-b-0">
                                        <div class="bg-purple-100 p-2 rounded-full flex-shrink-0">
                                            <i class="fas fa-plus text-purple-600"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-800">
                                                <span class="font-semibold"><?php echo htmlspecialchars($activity['activity_type']); ?>:</span>
                                                <?php echo htmlspecialchars($activity['details']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                By: <?php echo htmlspecialchars($activity['performed_by'] ?? 'System'); ?> • 
                                                <?php echo date('M d, Y g:i A', strtotime($activity['activity_time'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <!-- View All Students Button -->
                        <button id="view-students-btn" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg transition flex items-center justify-center">
                            <i class="fas fa-users mr-2"></i>View All Students
                        </button>
                        
                        <!-- Generate Reports Button - Updated to open modal -->
                        <button id="generate-report-btn" class="w-full bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg transition flex items-center justify-center">
                            <i class="fas fa-file-alt mr-2"></i>Generate Reports
                        </button>
                        
                        <a href="announcement.php" class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-3 rounded-lg transition">
                            <i class="fas fa-bullhorn mr-2"></i>Make Announcement
                        </a>
                        <a href="manage_users.php?role=teacher" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white text-center py-3 rounded-lg transition">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>Manage All Teachers
                        </a>
                    </div>
                </div>

                <!-- Program Chairs -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-user-tie text-indigo-500 mr-2"></i>Program Chairs
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($program_chairs)): ?>
                            <p class="text-gray-500 text-center text-sm py-4">No program chairs assigned</p>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($program_chairs as $chair): ?>
                                    <div class="flex items-start p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                        <div class="bg-indigo-100 p-2 rounded-full mr-3">
                                            <i class="fas fa-user-tie text-indigo-600"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($chair['name']); ?></p>
                                            <p class="text-xs text-gray-600 truncate"><?php echo htmlspecialchars($chair['email']); ?></p>
                                            <?php if (!empty($chair['department'])): ?>
                                            <p class="text-xs text-indigo-600 mt-1">
                                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($chair['department']); ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-line text-purple-600 mr-2"></i>Activity Summary
                    </h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center pb-2 border-b border-purple-200">
                            <span class="text-gray-600">Teacher Activity Rate:</span>
                            <span class="font-semibold <?php echo $teacher_activity_rate >= 80 ? 'text-green-600' : ($teacher_activity_rate >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                <?php echo $teacher_activity_rate; ?>%
                            </span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-purple-200">
                            <span class="text-gray-600">Student Enrollment:</span>
                            <span class="font-semibold <?php echo $student_enrollment_rate >= 90 ? 'text-green-600' : ($student_enrollment_rate >= 70 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                <?php echo $student_enrollment_rate; ?>%
                            </span>
                        </div>
                        <div class="flex justify-between items-center pb-2 border-b border-purple-200">
                            <span class="text-gray-600">Active Sections:</span>
                            <span class="font-semibold text-gray-800"><?php echo $stats['total_sections']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Programs Running:</span>
                            <span class="font-semibold text-gray-800"><?php echo $stats['total_programs']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- System Health Indicator -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-gray-800 mb-4">
                        <i class="fas fa-heartbeat text-red-500 mr-2"></i>System Health
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Teacher Assignment</span>
                            <span class="text-sm font-semibold <?php echo $inactive_teachers == 0 ? 'text-green-600' : 'text-orange-600'; ?>">
                                <i class="fas <?php echo $inactive_teachers == 0 ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo $inactive_teachers == 0 ? 'Good' : 'Needs Attention'; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Student Enrollment</span>
                            <span class="text-sm font-semibold <?php echo $unassigned_students == 0 ? 'text-green-600' : 'text-orange-600'; ?>">
                                <i class="fas <?php echo $unassigned_students == 0 ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo $unassigned_students == 0 ? 'Good' : 'Needs Attention'; ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Section Coverage</span>
                            <span class="text-sm font-semibold <?php echo $unassigned_sections == 0 ? 'text-green-600' : 'text-orange-600'; ?>">
                                <i class="fas <?php echo $unassigned_sections == 0 ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo $unassigned_sections == 0 ? 'Good' : 'Needs Attention'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-3x text-green-600 mb-4"></i>
            <p class="text-lg font-semibold">Generating Report...</p>
            <p class="text-gray-600 mt-2">Please wait while we prepare your report</p>
            <div class="mt-4 text-xs text-gray-500">
                <p>PDF generation powered by TCPDF</p>
                <p>This may take a few moments</p>
            </div>
        </div>
    </div>

    <!-- Students Modal -->
    <div id="students-modal" class="modal">
        <div class="modal-content">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 rounded-t-lg flex justify-between items-center modal-header">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-users mr-2"></i>All Students (<?php echo count($all_students); ?>)
                </h2>
                <span class="close-modal text-white text-2xl cursor-pointer hover:text-gray-200">&times;</span>
            </div>
            
            <div class="p-6" style="max-height: calc(90vh - 140px); overflow-y: auto;">
                <!-- Filter Controls -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-blue-50 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course</label>
                        <select id="course-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course']); ?>"><?php echo htmlspecialchars($course['course']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                        <select id="year-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Years</option>
                            <?php foreach ($year_levels as $year): ?>
                                <option value="<?php echo htmlspecialchars($year['year_level']); ?>">Year <?php echo htmlspecialchars($year['year_level']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                        <select id="section-filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['section']); ?>"><?php echo htmlspecialchars($section['section']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="student-search" placeholder="Search by name or ID..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Student Count Summary -->
                <div class="mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-gray-600">Showing </span>
                            <span id="student-count" class="font-bold text-blue-600"><?php echo count($all_students); ?></span>
                            <span class="text-gray-600"> students</span>
                        </div>
                        <div class="flex space-x-2">
                            <button id="export-csv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                <i class="fas fa-file-export mr-2"></i>Export CSV
                            </button>
                            <button id="print-list" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                                <i class="fas fa-print mr-2"></i>Print List
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">ID</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Course</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Year</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Section</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Enrolled</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="student-table-body">
                            <?php foreach ($all_students as $student): 
                                $enrollment_status = !empty($student['section']) ? 'Enrolled' : 'Unassigned';
                                $status_class = $enrollment_status === 'Enrolled' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                            ?>
                            <tr class="student-row" 
                                data-course="<?php echo htmlspecialchars($student['course'] ?? ''); ?>"
                                data-year="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>"
                                data-section="<?php echo htmlspecialchars($student['section'] ?? ''); ?>"
                                data-search="<?php echo htmlspecialchars(strtolower(($student['name'] ?? '') . ' ' . ($student['id'] ?? ''))); ?>">
                                <td class="px-4 py-3 text-sm text-gray-800 font-mono">
                                    <?php echo htmlspecialchars($student['id'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($student['name'] ?? 'N/A'); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($student['email'] ?? ''); ?></p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded"><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span class="font-medium"><?php echo htmlspecialchars($student['year_level'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <?php echo htmlspecialchars($student['section'] ?? 'No Section'); ?>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 <?php echo $status_class; ?> rounded-full text-xs font-medium">
                                        <?php echo $enrollment_status; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?php echo !empty($student['created_at']) ? date('M d, Y', strtotime($student['created_at'])) : 'N/A'; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div id="no-students-message" class="hidden text-center py-12">
                    <i class="fas fa-user-graduate text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700">No students found</h3>
                    <p class="text-gray-500 mt-1">Try adjusting your filters</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Reports Modal -->
    <div id="reports-modal" class="modal">
        <div class="modal-content">
            <div class="bg-gradient-to-r from-green-600 to-green-800 px-6 py-4 rounded-t-lg flex justify-between items-center modal-header">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-file-alt mr-2"></i>Generate Reports
                </h2>
                <span class="close-reports-modal text-white text-2xl cursor-pointer hover:text-gray-200">&times;</span>
            </div>
            
            <div class="p-6" style="max-height: calc(90vh - 140px); overflow-y: auto;">
                <form id="report-form" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" name="generate_report" value="1">
                    
                    <div class="space-y-6">
                        <!-- Report Type Selection -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Report Type</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <!-- Teachers Report -->
                                <div class="report-option">
                                    <input type="radio" id="report-teachers" name="report_type" value="teachers" class="hidden" checked>
                                    <label for="report-teachers" class="block cursor-pointer h-full">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 transition h-full report-card" data-value="teachers">
                                            <div class="flex items-center">
                                                <div class="w-5 h-5 mr-3 border-2 border-gray-300 rounded-full flex items-center justify-center radio-circle">
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">Teachers Report</h4>
                                                    <p class="text-xs text-gray-600 mt-1">Teacher activity and assignments</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Students Report -->
                                <div class="report-option">
                                    <input type="radio" id="report-students" name="report_type" value="students" class="hidden">
                                    <label for="report-students" class="block cursor-pointer h-full">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 transition h-full report-card" data-value="students">
                                            <div class="flex items-center">
                                                <div class="w-5 h-5 mr-3 border-2 border-gray-300 rounded-full flex items-center justify-center radio-circle">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full radio-dot" style="display: none;"></div>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">Students Report</h4>
                                                    <p class="text-xs text-gray-600 mt-1">Student enrollment and distribution</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Combined Report -->
                                <div class="report-option">
                                    <input type="radio" id="report-both" name="report_type" value="both" class="hidden">
                                    <label for="report-both" class="block cursor-pointer h-full">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 transition h-full report-card" data-value="both">
                                            <div class="flex items-center">
                                                <div class="w-5 h-5 mr-3 border-2 border-gray-300 rounded-full flex items-center justify-center radio-circle">
                                                    <div class="w-2 h-2 bg-green-500 rounded-full radio-dot" style="display: none;"></div>
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">Combined Report</h4>
                                                    <p class="text-xs text-gray-600 mt-1">Both teachers and students data</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Format Selection -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Export Format</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <!-- CSV Format -->
                                <div class="format-option">
                                    <input type="radio" id="format-csv" name="format" value="csv" class="hidden" checked>
                                    <label for="format-csv" class="block cursor-pointer h-full">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 transition h-full format-card" data-value="csv">
                                            <div class="flex items-center">
                                                <div class="w-5 h-5 mr-3 border-2 border-gray-300 rounded-full flex items-center justify-center radio-circle">
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">CSV Format</h4>
                                                    <p class="text-xs text-gray-600 mt-1">Excel compatible, easy to analyze</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- PDF Format -->
                                <div class="format-option">
                                    <input type="radio" id="format-pdf" name="format" value="pdf" class="hidden">
                                    <label for="format-pdf" class="block cursor-pointer h-full">
                                        <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 transition h-full format-card" data-value="pdf">
                                            <div class="flex items-center">
                                                <div class="w-5 h-5 mr-3 border-2 border-gray-300 rounded-full flex items-center justify-center radio-circle">
                                                </div>
                                                <div>
                                                    <h4 class="font-medium text-gray-800">PDF Format</h4>
                                                    <p class="text-xs text-gray-600 mt-1">Printable, professional format</p>
                                                    <div class="pdf-features">
                                                        <div class="pdf-feature">
                                                            <i class="fas fa-check"></i> Professional layout
                                                        </div>
                                                        <div class="pdf-feature">
                                                            <i class="fas fa-check"></i> Color-coded status
                                                        </div>
                                                        <div class="pdf-feature">
                                                            <i class="fas fa-check"></i> Summary statistics
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Report Preview -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Report Preview</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div id="report-preview" class="space-y-2">
                                    <div class="report-preview-item flex justify-between">
                                        <span class="text-gray-600">Teachers Data:</span>
                                        <span class="font-medium" id="teachers-count"><?php echo $stats['total_teachers']; ?> teachers</span>
                                    </div>
                                    <div class="report-preview-item flex justify-between">
                                        <span class="text-gray-600">Students Data:</span>
                                        <span class="font-medium" id="students-count"><?php echo $stats['total_students']; ?> students</span>
                                    </div>
                                    <div class="report-preview-item flex justify-between">
                                        <span class="text-gray-600">Active Teachers:</span>
                                        <span class="font-medium text-green-600"><?php echo $active_teachers; ?> (<?php echo $teacher_activity_rate; ?>%)</span>
                                    </div>
                                    <div class="report-preview-item flex justify-between">
                                        <span class="text-gray-600">Enrolled Students:</span>
                                        <span class="font-medium text-green-600"><?php echo $stats['total_students'] - $unassigned_students; ?> (<?php echo $student_enrollment_rate; ?>%)</span>
                                    </div>
                                    <!-- Export Format Display -->
                                    <div class="report-preview-item flex justify-between">
                                        <span class="text-gray-600">Export Format:</span>
                                        <span class="format-indicator csv" id="format-display">CSV</span>
                                    </div>
                                    <div class="pt-2 border-t mt-3">
                                        <p class="text-xs text-gray-500">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            PDF reports include professional formatting with color-coded status indicators and summary statistics.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 pt-4 mt-6 sticky-buttons">
                        <button type="button" class="close-reports-modal px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" id="generate-report-submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center shadow-md">
                            <i class="fas fa-download mr-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Teacher filter functionality
        const statusFilter = document.getElementById('status-filter');
        const teacherRows = document.querySelectorAll('.teacher-row');
        const activeCountSpan = document.getElementById('active-count');
        const inactiveCountSpan = document.getElementById('inactive-count');
        
        // Count initial active/inactive teachers
        let activeCount = 0;
        let inactiveCount = 0;
        
        teacherRows.forEach(row => {
            if (row.getAttribute('data-status') === 'active') {
                activeCount++;
            } else {
                inactiveCount++;
            }
        });
        
        // Update counts
        activeCountSpan.textContent = activeCount;
        inactiveCountSpan.textContent = inactiveCount;
        
        // Filter function
        function filterTeachers(status) {
            teacherRows.forEach(row => {
                if (status === 'all') {
                    row.classList.remove('hidden');
                } else if (row.getAttribute('data-status') === status) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }
        
        // Event listener for filter change
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                filterTeachers(this.value);
            });
        }

        // Modal elements
        const studentsModal = document.getElementById('students-modal');
        const viewStudentsBtn = document.getElementById('view-students-btn');
        const closeStudentsModal = document.querySelector('.close-modal');
        const reportsModal = document.getElementById('reports-modal');
        const generateReportBtn = document.getElementById('generate-report-btn');
        const closeReportsModal = document.querySelector('.close-reports-modal');
        const loadingOverlay = document.getElementById('loading-overlay');

        // Open students modal
        if (viewStudentsBtn) {
            viewStudentsBtn.addEventListener('click', function() {
                studentsModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        }

        // Open reports modal
        if (generateReportBtn) {
            generateReportBtn.addEventListener('click', function() {
                reportsModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                updateReportPreview();
            });
        }

        // Close modals
        function closeModal(modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        if (closeStudentsModal) {
            closeStudentsModal.addEventListener('click', () => closeModal(studentsModal));
        }

        if (closeReportsModal) {
            closeReportsModal.addEventListener('click', () => closeModal(reportsModal));
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === studentsModal) closeModal(studentsModal);
            if (event.target === reportsModal) closeModal(reportsModal);
            if (event.target === loadingOverlay) loadingOverlay.style.display = 'none';
        });

        // Students filter functionality
        const studentRows = document.querySelectorAll('.student-row');
        const courseFilter = document.getElementById('course-filter');
        const yearFilter = document.getElementById('year-filter');
        const sectionFilter = document.getElementById('section-filter');
        const studentSearch = document.getElementById('student-search');
        const studentCountSpan = document.getElementById('student-count');
        const noStudentsMessage = document.getElementById('no-students-message');
        const exportCsvBtn = document.getElementById('export-csv');
        const printListBtn = document.getElementById('print-list');

        // Filter students function
        function filterStudents() {
            const courseValue = courseFilter ? courseFilter.value.toLowerCase() : '';
            const yearValue = yearFilter ? yearFilter.value : '';
            const sectionValue = sectionFilter ? sectionFilter.value : '';
            const searchValue = studentSearch ? studentSearch.value.toLowerCase() : '';
            
            let visibleCount = 0;
            
            studentRows.forEach(row => {
                const course = row.getAttribute('data-course').toLowerCase();
                const year = row.getAttribute('data-year');
                const section = row.getAttribute('data-section');
                const search = row.getAttribute('data-search');
                
                const courseMatch = !courseValue || course === courseValue;
                const yearMatch = !yearValue || year === yearValue;
                const sectionMatch = !sectionValue || section === sectionValue;
                const searchMatch = !searchValue || search.includes(searchValue);
                
                if (courseMatch && yearMatch && sectionMatch && searchMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update student count
            if (studentCountSpan) {
                studentCountSpan.textContent = visibleCount;
            }
            
            // Show/hide no students message
            if (noStudentsMessage) {
                noStudentsMessage.classList.toggle('hidden', visibleCount > 0);
            }
        }

        // Add event listeners for filters
        if (courseFilter) courseFilter.addEventListener('change', filterStudents);
        if (yearFilter) yearFilter.addEventListener('change', filterStudents);
        if (sectionFilter) sectionFilter.addEventListener('change', filterStudents);
        if (studentSearch) studentSearch.addEventListener('input', filterStudents);

        // Export CSV for students modal
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', function() {
                const visibleRows = Array.from(studentRows).filter(row => row.style.display !== 'none');
                
                if (visibleRows.length === 0) {
                    alert('No students to export!');
                    return;
                }
                
                let csvContent = "ID,Name,Email,Course,Year Level,Section,Status,Enrollment Date\n";
                
                visibleRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    const rowData = Array.from(cells).map(cell => {
                        if (cell.querySelector('.bg-green-100') || cell.querySelector('.bg-yellow-100')) {
                            const statusSpan = cell.querySelector('span');
                            return statusSpan ? statusSpan.textContent.trim() : '';
                        }
                        if (cell.querySelector('div')) {
                            const nameDiv = cell.querySelector('div');
                            const name = nameDiv.querySelector('p.font-medium')?.textContent.trim() || '';
                            return name;
                        }
                        return cell.textContent.trim();
                    });
                    csvContent += '"' + rowData.join('","') + '"' + '\n';
                });
                
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `students_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                alert(`Exported ${visibleRows.length} students to CSV`);
            });
        }

        // Print functionality
        if (printListBtn) {
            printListBtn.addEventListener('click', function() {
                const visibleRows = Array.from(studentRows).filter(row => row.style.display !== 'none');
                
                if (visibleRows.length === 0) {
                    alert('No students to print!');
                    return;
                }
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Student List - ${new Date().toISOString().split('T')[0]}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            h1 { color: #1e40af; border-bottom: 2px solid #1e40af; padding-bottom: 10px; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th { background-color: #f3f4f6; text-align: left; padding: 10px; border: 1px solid #d1d5db; }
                            td { padding: 8px; border: 1px solid #d1d5db; }
                            .header-info { margin-bottom: 20px; color: #4b5563; }
                            .footer { margin-top: 30px; font-size: 12px; color: #6b7280; text-align: center; }
                        </style>
                    </head>
                    <body>
                        <h1>PLMUN LMS - Student List</h1>
                        <div class="header-info">
                            <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                            <p><strong>Total Students:</strong> ${visibleRows.length}</p>
                            <p><strong>Course:</strong> ${courseFilter ? courseFilter.value || 'All' : 'All'}</p>
                            <p><strong>Year Level:</strong> ${yearFilter ? yearFilter.value || 'All' : 'All'}</p>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Year</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `);
                
                visibleRows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    printWindow.document.write('<tr>');
                    cells.forEach((cell, index) => {
                        if (index < 6) {
                            let content = cell.textContent.trim();
                            if (index === 1) {
                                content = content.split('\n')[0];
                            }
                            printWindow.document.write(`<td>${content}</td>`);
                        }
                    });
                    printWindow.document.write('</tr>');
                });
                
                printWindow.document.write(`
                            </tbody>
                        </table>
                        <div class="footer">
                            <p>PLMUN Learning Management System - Generated by Dean Dashboard</p>
                        </div>
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
                printWindow.print();
            });
        }

        // Radio button functionality for reports
        function initializeRadioButtons() {
            // Report type radio buttons
            const reportOptions = document.querySelectorAll('.report-option');
            const formatOptions = document.querySelectorAll('.format-option');
            
            // Handle report type selection
            reportOptions.forEach(option => {
                const radio = option.querySelector('input[type="radio"]');
                const card = option.querySelector('.report-card');
                
                if (radio.checked) {
                    card.classList.add('selected');
                }
                
                option.addEventListener('click', function(e) {
                    if (e.target.type === 'radio') return;
                    
                    // Uncheck all report radios
                    reportOptions.forEach(r => {
                        r.querySelector('input[type="radio"]').checked = false;
                        r.querySelector('.report-card').classList.remove('selected');
                    });
                    
                    // Check this radio
                    radio.checked = true;
                    card.classList.add('selected');
                    
                    updateReportPreview();
                });
                
                // Also handle direct radio clicks
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        reportOptions.forEach(r => {
                            r.querySelector('.report-card').classList.remove('selected');
                        });
                        card.classList.add('selected');
                        updateReportPreview();
                    }
                });
            });
            
            // Handle format selection
            formatOptions.forEach(option => {
                const radio = option.querySelector('input[type="radio"]');
                const card = option.querySelector('.format-card');
                
                if (radio.checked) {
                    card.classList.add('selected');
                }
                
                option.addEventListener('click', function(e) {
                    if (e.target.type === 'radio') return;
                    
                    // Uncheck all format radios
                    formatOptions.forEach(f => {
                        f.querySelector('input[type="radio"]').checked = false;
                        f.querySelector('.format-card').classList.remove('selected');
                    });
                    
                    // Check this radio
                    radio.checked = true;
                    card.classList.add('selected');
                    
                    updateReportPreview();
                });
                
                // Also handle direct radio clicks
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        formatOptions.forEach(f => {
                            f.querySelector('.format-card').classList.remove('selected');
                        });
                        card.classList.add('selected');
                        updateReportPreview();
                    }
                });
            });
        }

        // Initialize radio buttons
        initializeRadioButtons();

        // Update report preview
        function updateReportPreview() {
            const selectedReport = document.querySelector('.report-option input[type="radio"]:checked');
            const selectedFormat = document.querySelector('.format-option input[type="radio"]:checked');
            
            if (!selectedReport || !selectedFormat) return;
            
            const format = selectedFormat.value;
            const formatDisplay = format.toUpperCase();
            
            // Update the format display
            const formatElement = document.getElementById('format-display');
            if (formatElement) {
                formatElement.textContent = formatDisplay;
                formatElement.className = 'format-indicator ' + format;
            }
            
            // Update PDF features visibility
            const pdfFeatures = document.querySelectorAll('.pdf-features');
            pdfFeatures.forEach(feature => {
                if (format === 'pdf') {
                    feature.style.display = 'block';
                } else {
                    feature.style.display = 'none';
                }
            });
            
            console.log('Report preview updated:', {
                report_type: selectedReport.value,
                format: format
            });
        }

        // Reports modal functionality
        const reportForm = document.getElementById('report-form');
        const generateReportSubmit = document.getElementById('generate-report-submit');

        // Report form submission
        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get selected values
                const selectedReport = document.querySelector('.report-option input[type="radio"]:checked');
                const selectedFormat = document.querySelector('.format-option input[type="radio"]:checked');
                
                if (!selectedReport || !selectedFormat) {
                    alert('Please select report type and format');
                    return;
                }
                
                // Show loading overlay
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'flex';
                }
                
                // Show loading state on button
                if (generateReportSubmit) {
                    const originalText = generateReportSubmit.innerHTML;
                    generateReportSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
                    generateReportSubmit.disabled = true;
                }
                
                // Close modal after a short delay
                setTimeout(() => {
                    if (reportsModal) {
                        reportsModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                }, 300);
                
                // Create and submit hidden form
                const hiddenForm = document.createElement('form');
                hiddenForm.method = 'POST';
                hiddenForm.action = '<?php echo $_SERVER["PHP_SELF"]; ?>';
                hiddenForm.style.display = 'none';
                
                const reportTypeInput = document.createElement('input');
                reportTypeInput.type = 'hidden';
                reportTypeInput.name = 'report_type';
                reportTypeInput.value = selectedReport.value;
                
                const formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = selectedFormat.value;
                
                const generateInput = document.createElement('input');
                generateInput.type = 'hidden';
                generateInput.name = 'generate_report';
                generateInput.value = '1';
                
                hiddenForm.appendChild(reportTypeInput);
                hiddenForm.appendChild(formatInput);
                hiddenForm.appendChild(generateInput);
                document.body.appendChild(hiddenForm);
                
                // Log what's being submitted
                console.log('Generating report:', {
                    report_type: selectedReport.value,
                    format: selectedFormat.value
                });
                
                // Submit the form
                hiddenForm.submit();
                
                // Hide loading overlay after 10 seconds (fallback)
                setTimeout(() => {
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    if (generateReportSubmit) {
                        generateReportSubmit.disabled = false;
                        generateReportSubmit.innerHTML = '<i class="fas fa-download mr-2"></i>Generate Report';
                    }
                }, 10000);
            });
        }

        // Initial preview update
        updateReportPreview();
    });
    </script>
 <!-- Chatbase AI Assistant - Official Widget Integration -->
    <!-- This loads the chatbase.co widget automatically -->
    <script>
        (function() {
            const chatbotId = 'o5GQsfIkaTL9N3YOLBdU7'; // Your Chatbase chatbot ID
            
            // Load the official Chatbase widget script
            const script = document.createElement('script');
            script.src = 'https://www.chatbase.co/embed.min.js';
            script.setAttribute('chatbotId', chatbotId);
            script.setAttribute('domain', 'www.chatbase.co');
            script.defer = true;
            
            // Optional: Add some basic styling for the widget if needed
            const style = document.createElement('style');
            style.textContent = `
                /* Ensure Chatbase widget appears above other elements */
                .chatbase-chat-widget {
                    z-index: 9999 !important;
                }
                /* Adjust position if needed */
                .chatbase-chat-widget .chat-widget {
                    bottom: 20px !important;
                    right: 20px !important;
                }
            `;
            document.head.appendChild(style);
            
            document.head.appendChild(script);
            
            console.log('Chatbase AI Assistant loading for Dean dashboard...');
        })();
    </script>

</body>
</html>
