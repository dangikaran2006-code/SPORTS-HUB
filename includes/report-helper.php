<?php
/**
 * SportsHub - Report & Export Utilities Helper
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Clean CSV Exporter with UTF-8 BOM support for Excel compatibility
 */
function exportCsvReport($filename, array $headers, array $rows) {
    // Log audit trail for report export
    logAuditAction('Report Exported', 'Report', null, "Exported CSV report '{$filename}'");

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Insert UTF-8 BOM for Microsoft Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Write Header Row
    fputcsv($output, $headers);

    // Write Data Rows
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Print Header Component Helper
 */
function renderPrintHeader($title) {
    $collegeName = getSetting('college_name', 'Siddaganga Institute of Technology');
    $champTitle  = getSetting('championship_title', 'Inter-Department Sports Championship 2026');
    $year        = getSetting('academic_year', '2025-2026');
    $timestamp   = date('F d, Y h:i A');

    return "
    <div class=\"print-header\" style=\"text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #333;\">
      <h2 style=\"margin: 0 0 4px 0; font-size: 1.5rem; color: #000;\">" . htmlspecialchars($collegeName) . "</h2>
      <h3 style=\"margin: 0 0 4px 0; font-size: 1.2rem; color: #333;\">" . htmlspecialchars($champTitle) . " (AY " . htmlspecialchars($year) . ")</h3>
      <h4 style=\"margin: 0 0 6px 0; font-size: 1.05rem; color: #00e676;\">" . htmlspecialchars($title) . "</h4>
      <div style=\"font-size: 0.8rem; color: #666;\">Generated on: {$timestamp} | Official Championship Record</div>
    </div>
    ";
}
