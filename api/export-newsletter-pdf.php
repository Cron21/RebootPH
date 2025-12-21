<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    $newsletterId = $_GET['id'] ?? null;

    if (!$newsletterId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Newsletter ID is required']);
        exit;
    }

    // Fetch newsletter details
    $stmt = $conn->prepare("
        SELECT 
            NewsletterID,
            Title,
            Content,
            PublishDate,
            CreatedByAdminID,
            LastModifiedBy,
            LastModifiedDate
        FROM newsletter
        WHERE NewsletterID = ?
    ");
    $stmt->execute([$newsletterId]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newsletter) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Newsletter not found']);
        exit;
    }

    // Fetch images for this newsletter
    $imgStmt = $conn->prepare("SELECT ImagePath FROM images WHERE NewsletterID = ? ORDER BY ImageID ASC");
    $imgStmt->execute([$newsletterId]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    $newsletter['images'] = array_map(function($img) { return $img['ImagePath']; }, $images);

    // Helper: sanitize text - strip tags, decode entities, remove control/zero-width chars,
    // join spaced single letters (e.g., "E v e r y" => "Every"), collapse whitespace,
    // and normalize paragraph breaks.
    function sanitize_text($text) {
        if ($text === null) return '';
        // Strip tags and decode entities
        $s = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalize CRLF to LF
        $s = preg_replace("/\r\n?/", "\n", $s);
        // Remove zero-width and BOM
        $s = preg_replace('/[\x{200B}-\x{200F}\x{FEFF}]/u', '', $s);
        // Remove other non-printable control chars except newline
        $s = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
        // Replace non-breaking spaces and other separator spaces with normal space
        $s = preg_replace('/\x{00A0}/u', ' ', $s);
        $s = preg_replace('/\p{Zs}+/u', ' ', $s);
        // Join sequences of single letters separated by spaces (e.g., "E v e r y")
        $s = preg_replace_callback('/\b(?:[A-Za-z]\s+){2,}[A-Za-z]\b/u', function($m){
            return preg_replace('/\s+/u', '', $m[0]);
        }, $s);
        // Trim spaces on each line and collapse multiple spaces
        $lines = preg_split('/\n/u', $s);
        $lines = array_map(function($line){ return preg_replace('/\s+/u', ' ', trim($line)); }, $lines);
        $s = implode("\n", $lines);
        // Normalize multiple newlines to two (paragraph separator)
        $s = preg_replace('/\n{2,}/u', "\n\n", $s);
        return trim($s);
    }

    // Sanitize title and content for safe PDF export
    $newsletter['Title'] = sanitize_text($newsletter['Title'] ?? '');
    $newsletter['Content'] = sanitize_text($newsletter['Content'] ?? '');

    // Format publish date
    if ($newsletter['PublishDate']) {
        $date = new DateTime($newsletter['PublishDate']);
        $newsletter['PublishDateFormatted'] = $date->format('F d, Y');
    }

    echo json_encode([
        'success' => true,
        'newsletter' => $newsletter
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>