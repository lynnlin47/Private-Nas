<?php
session_start();

function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verifyCSRF(?string $t): bool { return !empty($t) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
$csrfToken = generateCSRFToken();

if (isset($_GET['set_theme'])) {
    $allowedThemes = ['quantum','master','dark','vspo','icloud','vapor','cyber','matrix'];
    if (in_array($_GET['set_theme'], $allowedThemes, true)) $_SESSION['active_theme'] = $_GET['set_theme'];
    $url = 'index.php'; if (!empty($_GET['folder'])) $url .= '?folder=' . urlencode($_GET['folder']);
    header('Location: ' . $url); exit;
}
$activeTheme = $_SESSION['active_theme'] ?? 'quantum';

$themes = [
    'quantum' => ['--primary'=>'#00f0ff','--dark'=>'#050510','--card'=>'#0a0a1f','--text'=>'#e8f4ff','--danger'=>'#ff0055','--btn-text'=>'#000','--item-bg'=>'#0f0f25','--input-bg'=>'#080815','--input-border'=>'#1f1f3f','--shadow'=>'rgba(0,240,255,0.3)','--overlay'=>'rgba(0,0,0,0.85)','--panel-bg'=>'rgba(10,10,30,0.85)','--accent2'=>'#ff00aa','--accent3'=>'#aaff00'],
    'master'  => ['--primary'=>'#00d2ff','--dark'=>'#0f0f0f','--card'=>'#1a1a1a','--text'=>'#e0e0e0','--danger'=>'#ff4d4d','--btn-text'=>'#000','--item-bg'=>'#222','--input-bg'=>'#111','--input-border'=>'#444','--shadow'=>'rgba(0,0,0,0.5)','--overlay'=>'rgba(0,0,0,0.8)','--panel-bg'=>'rgba(20,20,20,0.9)','--accent2'=>'#a855f7','--accent3'=>'#34c759'],
    'dark'    => ['--primary'=>'#8ab4f8','--dark'=>'#121212','--card'=>'#1e1e1e','--text'=>'#e8eaed','--danger'=>'#f28b82','--btn-text'=>'#000','--item-bg'=>'#292a2d','--input-bg'=>'#202124','--input-border'=>'#5f6368','--shadow'=>'rgba(0,0,0,0.6)','--overlay'=>'rgba(0,0,0,0.85)','--panel-bg'=>'rgba(30,30,30,0.9)','--accent2'=>'#e8eaed','--accent3'=>'#34c759'],
    'vspo'    => ['--primary'=>'#ff00d2','--dark'=>'#0f0518','--card'=>'#1f0b38','--text'=>'#f4e8ff','--danger'=>'#ff3366','--btn-text'=>'#fff','--item-bg'=>'#2d1656','--input-bg'=>'#1a082e','--input-border'=>'#4d238e','--shadow'=>'rgba(255,0,210,0.2)','--overlay'=>'rgba(15,5,24,0.9)','--panel-bg'=>'rgba(45,22,86,0.9)','--accent2'=>'#00ffff','--accent3'=>'#ffff00'],
    'icloud'  => ['--primary'=>'#007aff','--dark'=>'#f5f5f7','--card'=>'#ffffff','--text'=>'#1d1d1f','--danger'=>'#ff3b30','--btn-text'=>'#fff','--item-bg'=>'#ffffff','--input-bg'=>'#f0f0f0','--input-border'=>'#d2d2d7','--shadow'=>'rgba(0,0,0,0.08)','--overlay'=>'rgba(255,255,255,0.7)','--panel-bg'=>'rgba(255,255,255,0.9)','--accent2'=>'#34c759','--accent3'=>'#ff9500'],
    'vapor'   => ['--primary'=>'#ff71ce','--dark'=>'#1a0033','--card'=>'#2d1b4e','--text'=>'#fffdff','--danger'=>'#ff0055','--btn-text'=>'#fff','--item-bg'=>'#3d2b5e','--input-bg'=>'#1f0c3a','--input-border'=>'#7c3aed','--shadow'=>'rgba(255,113,206,0.3)','--overlay'=>'rgba(26,0,51,0.92)','--panel-bg'=>'rgba(45,27,78,0.92)','--accent2'=>'#01cdfe','--accent3'=>'#05ffa1'],
    'cyber'   => ['--primary'=>'#fcee0a','--dark'=>'#0a0a0a','--card'=>'#161616','--text'=>'#f0f0f0','--danger'=>'#ff003c','--btn-text'=>'#000','--item-bg'=>'#1f1f1f','--input-bg'=>'#0a0a0a','--input-border'=>'#3d3d3d','--shadow'=>'rgba(252,238,10,0.2)','--overlay'=>'rgba(0,0,0,0.9)','--panel-bg'=>'rgba(20,20,20,0.95)','--accent2'=>'#ff003c','--accent3'=>'#00ff9f'],
    'matrix'  => ['--primary'=>'#00ff41','--dark'=>'#000000','--card'=>'#001a00','--text'=>'#00ff41','--danger'=>'#ff0000','--btn-text'=>'#000','--item-bg'=>'#001100','--input-bg'=>'#000800','--input-border'=>'#00ff41','--shadow'=>'rgba(0,255,65,0.3)','--overlay'=>'rgba(0,0,0,0.92)','--panel-bg'=>'rgba(0,26,0,0.92)','--accent2'=>'#008f11','--accent3'=>'#00ff41'],
];
$themeVars = $themes[$activeTheme] ?? $themes['quantum'];
$themeCSS = ''; foreach ($themeVars as $k => $v) $themeCSS .= "$k: $v; ";

$baseDir  = __DIR__ . '/uploads';
$imgfdDir = __DIR__ . '/imgfd';
$metaFile = $imgfdDir . '/meta.json';
$tagsFile = $imgfdDir . '/tags.json';
$ratingsFile = $imgfdDir . '/ratings.json';
$commentsFile = $imgfdDir . '/comments.json';
$activityFile = $imgfdDir . '/activity.json';

if (!is_dir($baseDir))  mkdir($baseDir, 0755, true);
if (!is_dir($imgfdDir)) mkdir($imgfdDir, 0755, true);
if (!file_exists($baseDir . '/.htaccess')) file_put_contents($baseDir . '/.htaccess', "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps|pl|py|jsp|asp|aspx|htm|html|shtml|sh|cgi|js)$\">\nForceType text/plain\n</FilesMatch>\nphp_flag engine off\n");
foreach ([$metaFile, $tagsFile, $ratingsFile, $commentsFile, $activityFile] as $f) {
    if (!file_exists($f)) file_put_contents($f, in_array($f, [$metaFile]) ? '[]' : '{}');
}

$folderMeta = json_decode(file_get_contents($metaFile), true) ?: [];
$tagsData   = json_decode(file_get_contents($tagsFile), true) ?: [];
$ratingsData = json_decode(file_get_contents($ratingsFile), true) ?: [];
$commentsData = json_decode(file_get_contents($commentsFile), true) ?: [];
$activityData = json_decode(file_get_contents($activityFile), true) ?: [];

$realBase = realpath($baseDir);
$currentFolder = isset($_GET['folder']) ? trim((string)$_GET['folder'], '/') : '';
$targetPath = realpath($realBase . '/' . $currentFolder);
if ($targetPath === false || strpos($targetPath, $realBase) !== 0) { $workDir = $realBase; $currentFolder = ''; }
else { $workDir = $targetPath; }

$message = ''; $messageType = 'info';

function deleteDirectory(string $dir): bool {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return @unlink($dir);
    foreach (scandir($dir) as $item) { if ($item === '.' || $item === '..') continue; if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false; }
    return @rmdir($dir);
}
function getAllFolders(string $dir, string $basePath = ''): array {
    $result = [''];
    if (!is_dir($dir)) return $result;
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        if (is_dir($dir . '/' . $item)) {
            $relPath = ltrim($basePath . '/' . $item, '/');
            $result[] = $relPath;
            $result = array_merge($result, getAllFolders($dir . '/' . $item, $relPath));
        }
    }
    return $result;
}
function formatFileSize(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}
function safeName(string $name, bool $isFolder = false): string {
    $name = trim($name);
    $clean = $isFolder ? preg_replace('/[^\p{L}\p{N}\-_]/u', '_', $name) : preg_replace('/[^\p{L}\p{N}\-_.\(\) ]/u', '_', $name);
    return $clean ?? '';
}
function detectFileType(string $ext): string {
    $ext = strtolower($ext);
    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','ico','bmp','heic'], true)) return 'image';
    if (in_array($ext, ['mp4','webm','mov','mkv','avi','m4v','flv','wmv','mpg','mpeg','3gp','ts','m2ts','mts'], true)) return 'video';
    if (in_array($ext, ['mp3','wav','ogg','m4a','flac','aac'], true)) return 'audio';
    if (in_array($ext, ['txt','json','php','css','js','html','md','xml','csv','log','yaml','yml'], true)) return 'text';
    if ($ext === 'pdf') return 'pdf';
    if (in_array($ext, ['zip','rar','7z','tar','gz','bz2'], true)) return 'archive';
    if (in_array($ext, ['doc','docx','xls','xlsx','ppt','pptx','odt'], true)) return 'doc';
    return 'other';
}
function fileTypeIcon(string $type): string {
    $m = ['image'=>'fa-file-image','video'=>'fa-file-video','audio'=>'fa-file-audio','text'=>'fa-file-code','pdf'=>'fa-file-pdf','archive'=>'fa-file-archive','doc'=>'fa-file-word','other'=>'fa-file'];
    return $m[$type] ?? 'fa-file';
}
function fileTypeColor(string $type): string {
    $m = ['image'=>'bg-pink-500/20 text-pink-300','video'=>'bg-red-500/20 text-red-300','audio'=>'bg-blue-500/20 text-blue-300','text'=>'bg-green-500/20 text-green-300','pdf'=>'bg-red-500/20 text-red-300','archive'=>'bg-yellow-500/20 text-yellow-300','doc'=>'bg-indigo-500/20 text-indigo-300','other'=>'bg-gray-500/20 text-gray-300'];
    return $m[$type] ?? 'bg-gray-500/20 text-gray-300';
}
function dirSize(string $dir): int {
    $size = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) $size += $file->getSize();
    return $size;
}
function logActivity(array &$activityData, string $action, string $item): void {
    $activityData[] = ['action' => $action, 'item' => $item, 'time' => time()];
    if (count($activityData) > 100) array_shift($activityData);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['secret_auth'])) {
    $csrfOk = verifyCSRF($_POST['csrf_token'] ?? null);
    if (!$csrfOk) { $message = 'เซสชันหมดอายุ รีเฟรชแล้วลองใหม่'; $messageType = 'error'; }
    else {

        if (isset($_POST['unlock_folder'])) {
            $cf = (string)($_POST['target_folder'] ?? ''); $pass = (string)($_POST['folder_password'] ?? '');
            if (isset($folderMeta[$cf]) && password_verify($pass, $folderMeta[$cf]['password'])) $_SESSION['unlocked'][$cf] = true;
            else { $message = 'รหัสผ่านไม่ถูกต้อง!'; $messageType = 'error'; }
            header('Location: index.php?folder=' . urlencode($currentFolder)); exit;
        }
        if (isset($_POST['relock_folder'])) { unset($_SESSION['unlocked'][(string)($_POST['target_folder'] ?? '')]); header('Location: index.php?folder=' . urlencode($currentFolder)); exit; }

        if (isset($_POST['action_type']) && $_POST['action_type'] === 'set_tag') {
            $item = (string)($_POST['item_name'] ?? ''); $tag = trim((string)($_POST['tag'] ?? ''));
            $rp = ($currentFolder ? $currentFolder . '/' : '') . $item;
            if ($tag === '') unset($tagsData[$rp]); else $tagsData[$rp] = $tag;
            file_put_contents($tagsFile, json_encode($tagsData, JSON_PRETTY_PRINT));
            logActivity($activityData, 'tag', $rp);
            file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT));
            header('Location: index.php?folder=' . urlencode($currentFolder)); exit;
        }
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'set_rating') {
            $item = (string)($_POST['item_name'] ?? ''); $rating = (int)($_POST['rating'] ?? 0);
            $rp = ($currentFolder ? $currentFolder . '/' : '') . $item;
            if ($rating >= 1 && $rating <= 5) $ratingsData[$rp] = $rating;
            else unset($ratingsData[$rp]);
            file_put_contents($ratingsFile, json_encode($ratingsData, JSON_PRETTY_PRINT));
            header('Location: index.php?folder=' . urlencode($currentFolder)); exit;
        }
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'set_comment') {
            $item = (string)($_POST['item_name'] ?? ''); $comment = trim((string)($_POST['comment'] ?? ''));
            $rp = ($currentFolder ? $currentFolder . '/' : '') . $item;
            if ($comment === '') unset($commentsData[$rp]);
            else $commentsData[$rp] = ['text' => $comment, 'time' => time()];
            file_put_contents($commentsFile, json_encode($commentsData, JSON_PRETTY_PRINT));
            header('Location: index.php?folder=' . urlencode($currentFolder)); exit;
        }

        if (isset($_POST['action_type']) && $_POST['action_type'] === 'edit_folder_meta') {
            $ftn = basename((string)($_POST['target_folder'] ?? ''));
            $frp = ($currentFolder ? $currentFolder . '/' : '') . $ftn;
            $newName = safeName((string)($_POST['new_folder_name'] ?? ''), true);
            $nrp = ($currentFolder ? $currentFolder . '/' : '') . $newName;
            $isLocked = isset($_POST['is_locked']); $password = (string)($_POST['folder_password'] ?? ''); $cp = (string)($_POST['current_password'] ?? '');
            if (isset($folderMeta[$frp]) && !empty($folderMeta[$frp]['locked'])) {
                if (!password_verify($cp, $folderMeta[$frp]['password'])) { $message = 'รหัสผ่านปัจจุบันไม่ถูกต้อง!'; $messageType = 'error'; goto skip_action; }
            }
            $coverImage = $folderMeta[$frp]['cover'] ?? '';
            if (isset($_POST['folder_cover_select']) && $_POST['folder_cover_select'] !== '') {
                $si = basename((string)$_POST['folder_cover_select']);
                $sif = $workDir . '/' . $ftn . '/' . $si;
                if (is_file($sif)) {
                    $ext = strtolower(pathinfo($sif, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                        $cfn = $newName . '_cover_' . time() . '.' . $ext;
                        if (copy($sif, $imgfdDir . '/' . $cfn)) { if ($coverImage && file_exists($imgfdDir . '/' . $coverImage)) unlink($imgfdDir . '/' . $coverImage); $coverImage = $cfn; }
                    }
                }
            } elseif (isset($_POST['folder_cover_select']) && $_POST['folder_cover_select'] === '') {
                if ($coverImage && file_exists($imgfdDir . '/' . $coverImage)) unlink($imgfdDir . '/' . $coverImage);
                $coverImage = '';
            }
            if ($newName !== '' && $newName !== $ftn) {
                $op = $workDir . '/' . $ftn; $np = $workDir . '/' . $newName;
                if (!file_exists($np)) { rename($op, $np); if (isset($folderMeta[$frp])) unset($folderMeta[$frp]); $frp = $nrp; }
                else { $message = 'ชื่อโฟลเดอร์นี้มีอยู่แล้ว!'; $messageType = 'error'; goto skip_action; }
            }
            $folderMeta[$frp] = ['locked'=>$isLocked,'password'=>$isLocked?(!empty($password)?password_hash($password,PASSWORD_DEFAULT):($folderMeta[$frp]['password']??'')):'','cover'=>$coverImage];
            file_put_contents($metaFile, json_encode($folderMeta, JSON_PRETTY_PRINT));
            unset($_SESSION['unlocked'][$frp]);
            skip_action:
            if (!$message) { header('Location: index.php?folder=' . urlencode($currentFolder)); exit; }
        }

        if (isset($_POST['action_type']) && in_array($_POST['action_type'], ['batch_delete','batch_move'], true)) {
            $items = $_POST['selected_items'] ?? []; if (!is_array($items)) $items = [];
            $ok=0; $err=0;
            foreach ($items as $raw) {
                $item = basename((string)$raw); $sp = $workDir . '/' . $item; $rp = ($currentFolder ? $currentFolder . '/' : '') . $item;
                if (!file_exists($sp) || strpos(realpath($sp), $realBase) !== 0) { $err++; continue; }
                if ($_POST['action_type'] === 'batch_delete') {
                    if (is_dir($sp)) { if (deleteDirectory($sp)) $ok++; else $err++; }
                    else { if (unlink($sp)) $ok++; else $err++; }
                    if (isset($folderMeta[$rp])) { if (!empty($folderMeta[$rp]['cover']) && file_exists($imgfdDir . '/' . $folderMeta[$rp]['cover'])) unlink($imgfdDir . '/' . $folderMeta[$rp]['cover']); unset($folderMeta[$rp]); }
                    if (isset($tagsData[$rp])) unset($tagsData[$rp]);
                    if (isset($ratingsData[$rp])) unset($ratingsData[$rp]);
                    if (isset($commentsData[$rp])) unset($commentsData[$rp]);
                } elseif ($_POST['action_type'] === 'batch_move' && isset($_POST['target_folder'])) {
                    $tr = trim((string)$_POST['target_folder'], '/'); $td = realpath($realBase . '/' . $tr);
                    if ($td !== false && strpos($td, $realBase) === 0) {
                        $ft = $td . '/' . $item;
                        if (!file_exists($ft)) {
                            if (rename($sp, $ft)) {
                                $ok++; $nr = ($tr ? $tr . '/' : '') . $item;
                                if (isset($tagsData[$rp])) { $tagsData[$nr] = $tagsData[$rp]; unset($tagsData[$rp]); }
                                if (isset($ratingsData[$rp])) { $ratingsData[$nr] = $ratingsData[$rp]; unset($ratingsData[$rp]); }
                                if (isset($commentsData[$rp])) { $commentsData[$nr] = $commentsData[$rp]; unset($commentsData[$rp]); }
                            } else $err++;
                        } else $err++;
                    } else $err++;
                }
            }
            file_put_contents($metaFile, json_encode($folderMeta, JSON_PRETTY_PRINT));
            file_put_contents($tagsFile, json_encode($tagsData, JSON_PRETTY_PRINT));
            file_put_contents($ratingsFile, json_encode($ratingsData, JSON_PRETTY_PRINT));
            file_put_contents($commentsFile, json_encode($commentsData, JSON_PRETTY_PRINT));
            logActivity($activityData, $_POST['action_type'], count($items) . ' items');
            file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT));
            $al = $_POST['action_type'] === 'batch_delete' ? 'ลบ' : 'ย้าย';
            $message = "{$al}สำเร็จ {$ok}" . ($err > 0 ? " | ล้มเหลว {$err}" : ''); $messageType = $err > 0 && $ok === 0 ? 'error' : 'success';
            header('Location: index.php?folder=' . urlencode($currentFolder)); exit;
        }

        if (isset($_POST['action_type'], $_POST['item_name']) && in_array($_POST['action_type'], ['delete','rename','move'], true)) {
            $item = basename((string)$_POST['item_name']); $sp = $workDir . '/' . $item; $rp = ($currentFolder ? $currentFolder . '/' : '') . $item;
            if (file_exists($sp) && strpos(realpath($sp), $realBase) === 0) {
                $action = $_POST['action_type'];
                if ($action === 'delete') {
                    if (is_dir($sp)) deleteDirectory($sp); else unlink($sp);
                    if (isset($folderMeta[$rp])) { if (!empty($folderMeta[$rp]['cover']) && file_exists($imgfdDir . '/' . $folderMeta[$rp]['cover'])) unlink($imgfdDir . '/' . $folderMeta[$rp]['cover']); unset($folderMeta[$rp]); file_put_contents($metaFile, json_encode($folderMeta, JSON_PRETTY_PRINT)); }
                    if (isset($tagsData[$rp])) { unset($tagsData[$rp]); file_put_contents($tagsFile, json_encode($tagsData, JSON_PRETTY_PRINT)); }
                    if (isset($ratingsData[$rp])) { unset($ratingsData[$rp]); file_put_contents($ratingsFile, json_encode($ratingsData, JSON_PRETTY_PRINT)); }
                    if (isset($commentsData[$rp])) { unset($commentsData[$rp]); file_put_contents($commentsFile, json_encode($commentsData, JSON_PRETTY_PRINT)); }
                    logActivity($activityData, 'delete', $rp); file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT));
                    $message = "ลบ '{$item}' เรียบร้อย"; $messageType = 'success';
                }
                elseif ($action === 'rename' && !empty($_POST['new_name'])) {
                    $isDir = is_dir($sp); $nn = safeName((string)$_POST['new_name'], $isDir);
                    if ($nn !== '' && $nn !== $item) {
                        $np = $workDir . '/' . $nn;
                        if (!file_exists($np)) {
                            rename($sp, $np); $nr = ($currentFolder ? $currentFolder . '/' : '') . $nn;
                            if (isset($tagsData[$rp])) { $tagsData[$nr] = $tagsData[$rp]; unset($tagsData[$rp]); file_put_contents($tagsFile, json_encode($tagsData, JSON_PRETTY_PRINT)); }
                            if (isset($ratingsData[$rp])) { $ratingsData[$nr] = $ratingsData[$rp]; unset($ratingsData[$rp]); file_put_contents($ratingsFile, json_encode($ratingsData, JSON_PRETTY_PRINT)); }
                            if (isset($commentsData[$rp])) { $commentsData[$nr] = $commentsData[$rp]; unset($commentsData[$rp]); file_put_contents($commentsFile, json_encode($commentsData, JSON_PRETTY_PRINT)); }
                            if (isset($folderMeta[$rp])) { $folderMeta[$nr] = $folderMeta[$rp]; unset($folderMeta[$rp]); file_put_contents($metaFile, json_encode($folderMeta, JSON_PRETTY_PRINT)); }
                            logActivity($activityData, 'rename', "$item → $nn"); file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT));
                            $message = 'เปลี่ยนชื่อเรียบร้อย'; $messageType = 'success';
                        } else { $message = 'ชื่อนี้มีอยู่แล้ว'; $messageType = 'error'; }
                    }
                }
                elseif ($action === 'move' && isset($_POST['target_folder'])) {
                    $tr = trim((string)$_POST['target_folder'], '/'); $td = realpath($realBase . '/' . $tr);
                    if ($td !== false && strpos($td, $realBase) === 0) {
                        $ft = $td . '/' . $item;
                        if (!file_exists($ft)) {
                            rename($sp, $ft); $nr = ($tr ? $tr . '/' : '') . $item;
                            if (isset($tagsData[$rp])) { $tagsData[$nr] = $tagsData[$rp]; unset($tagsData[$rp]); file_put_contents($tagsFile, json_encode($tagsData, JSON_PRETTY_PRINT)); }
                            if (isset($ratingsData[$rp])) { $ratingsData[$nr] = $ratingsData[$rp]; unset($ratingsData[$rp]); file_put_contents($ratingsFile, json_encode($ratingsData, JSON_PRETTY_PRINT)); }
                            if (isset($commentsData[$rp])) { $commentsData[$nr] = $commentsData[$rp]; unset($commentsData[$rp]); file_put_contents($commentsFile, json_encode($commentsData, JSON_PRETTY_PRINT)); }
                            logActivity($activityData, 'move', "$item → /$tr"); file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT));
                            $message = "ย้าย '{$item}' สำเร็จ"; $messageType = 'success';
                        } else { $message = 'มีชื่อซ้ำในปลายทาง'; $messageType = 'error'; }
                    }
                }
            }
            if (!$message) { header('Location: index.php?folder=' . urlencode($currentFolder)); exit; }
        }

        if (isset($_POST['new_folder']) && !empty($_POST['folder_name'])) {
            $fn = safeName((string)$_POST['folder_name'], true);
            if ($fn !== '') {
                $np = $workDir . '/' . $fn;
                if (!file_exists($np)) { mkdir($np, 0755, true); logActivity($activityData, 'mkdir', $fn); file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT)); $message = "สร้างโฟลเดอร์ '{$fn}' แล้ว"; $messageType = 'success'; }
                else { $message = 'โฟลเดอร์นี้มีอยู่แล้ว'; $messageType = 'error'; }
            }
            if (!$message) { header('Location: index.php?folder=' . urlencode($currentFolder)); exit; }
        }

        if (isset($_FILES['images'])) {
            $totalFiles = count($_FILES['images']['name']);
            $maxFiles = 20; $maxSize = 100 * 1024 * 1024;
            if ($totalFiles > $maxFiles) { $message = "อัพโหลดได้สูงสุด {$maxFiles} ไฟล์ (เลือก {$totalFiles})"; $messageType = 'error'; }
            else {
                $sc = 0; $em = [];
                $allowedMime = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml','image/bmp','video/mp4','video/webm','video/quicktime','video/x-matroska','video/x-msvideo','audio/mpeg','audio/wav','audio/ogg','audio/mp4','audio/x-m4a','audio/flac','audio/aac','application/pdf','text/plain','application/json','text/css','application/javascript','text/html','text/markdown','text/csv','application/xml','application/zip','application/x-rar-compressed','application/x-7z-compressed','application/x-tar','application/gzip','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation','application/octet-stream'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                for ($i = 0; $i < $totalFiles; $i++) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) { $em[] = $_FILES['images']['name'][$i] . ' (err)'; continue; }
                    if ($_FILES['images']['size'][$i] > $maxSize) { $em[] = $_FILES['images']['name'][$i] . ' (ใหญ่เกิน 100MB)'; continue; }
                    $fn = basename($_FILES['images']['name'][$i]); $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                    $sfn = preg_replace('/[^\p{L}\p{N}\-_]/u', '_', pathinfo($fn, PATHINFO_FILENAME)) ?? 'file';
                    $ffn = $sfn . '_' . time() . '_' . $i . '.' . $ext; $fp = $workDir . '/' . $ffn;
                    $mime = $finfo->file($_FILES['images']['tmp_name'][$i]);
                    if (!in_array($mime, $allowedMime, true) && $mime !== 'application/octet-stream') { $em[] = $fn . " ({$mime} ไม่อนุญาต)"; continue; }
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $fp)) $sc++;
                    else $em[] = $fn . ' (save fail)';
                }
                if ($sc > 0) { $message = "อัพโหลดสำเร็จ {$sc} ไฟล์" . (!empty($em) ? ' | ล้มเหลว ' . count($em) : ''); $messageType = 'success'; logActivity($activityData, 'upload', $sc . ' files'); file_put_contents($activityFile, json_encode($activityData, JSON_PRETTY_PRINT)); }
                elseif (!empty($em)) { $message = implode(', ', $em); $messageType = 'error'; }
            }
        }
    }
}

$isCurrentLocked = false; $requireUnlockPath = $currentFolder;
if ($currentFolder !== '') {
    $pp = explode('/', $currentFolder); $cp = '';
    foreach ($pp as $p) {
        $cp .= ($cp === '' ? '' : '/') . $p;
        if (isset($folderMeta[$cp]) && !empty($folderMeta[$cp]['locked'])) {
            if (empty($_SESSION['unlocked'][$cp])) { $isCurrentLocked = true; $requireUnlockPath = $cp; break; }
        }
    }
}

$items = (!$isCurrentLocked && is_dir($workDir)) ? array_diff(scandir($workDir), ['.', '..']) : [];
$folders = []; $filesList = []; $mediaFiles = []; $imagesOnly = []; $videosOnly = []; $audioOnly = [];
$folderImagesMap = []; $folderVideosMap = []; $folderMediaPathMap = []; $folderSizes = [];

foreach ($items as $item) {
    $fullPath = $workDir . '/' . $item;
    if (is_dir($fullPath)) {
        $folders[] = $item;
        $imagesInFolder = []; $videosInFolder = [];
        $subItems = array_diff(scandir($fullPath), ['.', '..']);
        foreach ($subItems as $sub) {
            if (!is_dir($fullPath . '/' . $sub)) {
                $ext = strtolower(pathinfo($sub, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) $imagesInFolder[] = $sub;
                elseif (in_array($ext, ['mp4','webm','mov','mkv','avi','m4v'], true)) $videosInFolder[] = $sub;
            }
        }
        $folderImagesMap[$item] = $imagesInFolder;
        $folderVideosMap[$item] = $videosInFolder;
        $folderMediaPathMap[$item] = 'uploads/' . ($currentFolder ? $currentFolder . '/' : '') . $item . '/';
        $folderSizes[$item] = dirSize($fullPath);
    } else {
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        $type = detectFileType($ext); $size = filesize($fullPath); $mtime = filemtime($fullPath);
        $relPath = 'uploads/' . ($currentFolder ? $currentFolder . '/' : '') . $item;
        $fullRelPath = ($currentFolder ? $currentFolder . '/' : '') . $item;
        $itemData = ['name'=>$item,'ext'=>$ext,'type'=>$type,'size'=>$size,'sizeText'=>formatFileSize($size),'mtime'=>$mtime,'dateText'=>date('Y-m-d H:i', $mtime),'path'=>$relPath,'relPath'=>$fullRelPath,'tag'=>$tagsData[$fullRelPath] ?? '','rating'=>$ratingsData[$fullRelPath] ?? 0,'comment'=>$commentsData[$fullRelPath] ?? null,'md5'=> ''];
        $filesList[] = $itemData;
        if ($type !== 'other') $mediaFiles[] = $itemData;
        if ($type === 'image') $imagesOnly[] = $itemData;
        if ($type === 'video') $videosOnly[] = $itemData;
        if ($type === 'audio') $audioOnly[] = $itemData;
    }
}

natcasesort($folders);
$folders = array_values($folders);
usort($filesList, function($a, $b) { return strcasecmp($a['name'], $b['name']); });

$folderCount = count($folders);
$fileCount = count($filesList);
$imageCount = count($imagesOnly);
$videoCount = count($videosOnly);
$audioCount = count($audioOnly);

$totalStorageBytes = dirSize($realBase);
$totalStorageText = formatFileSize($totalStorageBytes);

$recentFiles = [];
foreach ($filesList as $f) if (time() - $f['mtime'] < 7 * 86400) $recentFiles[] = $f;
usort($recentFiles, function($a, $b) { return $b['mtime'] - $a['mtime']; });
$recentFiles = array_slice($recentFiles, 0, 12);

$typeStats = [];
foreach ($filesList as $f) {
    $t = $f['type'];
    if (!isset($typeStats[$t])) $typeStats[$t] = ['count' => 0, 'size' => 0];
    $typeStats[$t]['count']++; $typeStats[$t]['size'] += $f['size'];
}

$sizeMap = [];
foreach ($filesList as $f) $sizeMap[$f['size']] = ($sizeMap[$f['size']] ?? 0) + 1;
$duplicateCount = 0;
foreach ($sizeMap as $size => $cnt) if ($cnt > 1 && $size > 1024) $duplicateCount += ($cnt - 1);

$allAvailableFolders = getAllFolders($realBase);

$favorites = [];
foreach ($tagsData as $path => $tag) {
    if ($tag === 'favorite' || $tag === '★') {
        $fp = $realBase . '/' . $path;
        if (file_exists($fp)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $favorites[] = ['path' => 'uploads/' . $path, 'name' => basename($path), 'type' => detectFileType($ext), 'sizeText' => formatFileSize(filesize($fp)), 'rating' => $ratingsData[$path] ?? 0];
        }
    }
}
$topRated = [];
foreach ($ratingsData as $path => $r) {
    if ($r >= 4) {
        $fp = $realBase . '/' . $path;
        if (file_exists($fp)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $topRated[] = ['path' => 'uploads/' . $path, 'name' => basename($path), 'type' => detectFileType($ext), 'rating' => $r];
        }
    }
}
usort($topRated, function($a, $b) { return $b['rating'] - $a['rating']; });
$topRated = array_slice($topRated, 0, 8);

$recentActivity = array_slice(array_reverse($activityData), 0, 10);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NAS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
    colors: { primary:'var(--primary)','btn-text':'var(--btn-text)','th-dark':'var(--dark)','th-card':'var(--card)','th-text':'var(--text)',danger:'var(--danger)','item-bg':'var(--item-bg)','input-bg':'var(--input-bg)','input-border':'var(--input-border)',accent2:'var(--accent2)',accent3:'var(--accent3)'},
    fontFamily: { sans: ['Inter','Segoe UI','sans-serif'], mono: ['JetBrains Mono','monospace'] },
}}}
</script>
<style>
:root { <?php echo $themeCSS; ?> }
body { background: var(--dark); color: var(--text); transition: background-color .3s, color .3s; cursor: var(--cursor, default); }
body::before { content:''; position:fixed; inset:0; background: radial-gradient(circle at 20% 30%, var(--primary), transparent 40%), radial-gradient(circle at 80% 70%, var(--accent2), transparent 40%), radial-gradient(circle at 50% 50%, var(--accent3), transparent 30%); opacity:0.05; pointer-events:none; z-index:0; animation: bgPulse 20s ease-in-out infinite; }
@keyframes bgPulse { 0%,100% { transform: scale(1) rotate(0deg); } 50% { transform: scale(1.1) rotate(180deg); } }
.content-layer { position: relative; z-index: 1; }
::-webkit-scrollbar { width:10px; height:10px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background: var(--input-border); border-radius:5px; }
::-webkit-scrollbar-thumb:hover { background: var(--primary); }
.drop-active { outline: 3px dashed var(--primary); outline-offset: -10px; }
.glass { background: var(--panel-bg); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid var(--input-border); }
#mainModal { background:#000; }
.swiper { width:100%; height:100vh; background:#000; }
.swiper-slide { overflow:hidden; display:flex; justify-content:center; align-items:center; background:#000; }
.swiper-zoom-container { width:100%; height:100%; display:flex; justify-content:center; align-items:center; }
.audio-wrapper { background:var(--card); padding:40px; border-radius:20px; text-align:center; border:1px solid var(--input-border); }
.iframe-wrapper { width:85vw; height:85vh; background:#fff; border-radius:10px; border:none; }
.ui-hidden .top-panel, .ui-hidden .bottom-panel, .ui-hidden .swiper-button-next, .ui-hidden .swiper-button-prev, .ui-hidden .toggle-ui-standalone { opacity:0 !important; pointer-events:none !important; }
.ui-hidden .toggle-ui-standalone:hover { opacity:0.4 !important; }
@keyframes toastIn { from { transform: translateX(40px); opacity:0; } to { transform: translateX(0); opacity:1; } }
.toast { animation: toastIn .25s ease-out; }
.breadcrumb-link:hover { color: var(--primary); text-decoration: underline; }
.folder-preview-img { position:absolute; inset:0; background-size:cover; background-position:center; opacity:0; transition: opacity .8s ease; }
.folder-preview-img.active { opacity:1; }
.folder-preview-video { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0; transition: opacity .5s ease; pointer-events:none; }
.folder-preview-video.active { opacity:1; }
.item-card.selected { border-color: var(--primary) !important; box-shadow: 0 0 0 3px var(--primary), 0 0 30px var(--primary); }
.search-hidden { display: none !important; }
.list-view .item-card { height: auto !important; }
.list-view .grid { grid-template-columns: 1fr !important; }
.detail-view .grid { grid-template-columns: 1fr !important; }
.detail-view .item-card { height: auto !important; padding: 8px 12px; display: flex; align-items: center; gap: 12px; }
.detail-view .item-card > div:not(.action-btns-inline) { flex: 1; }
input[type="range"] { -webkit-appearance:none; appearance:none; height:6px; background: var(--input-border); border-radius:3px; outline:none; }
input[type="range"]::-webkit-slider-thumb { -webkit-appearance:none; appearance:none; width:18px; height:18px; border-radius:50%; background: var(--primary); cursor:pointer; box-shadow: 0 0 10px var(--primary); }
input[type="range"]::-moz-range-thumb { width:18px; height:18px; border-radius:50%; background: var(--primary); cursor:pointer; border:none; }
kbd { background: var(--input-bg); border: 1px solid var(--input-border); border-bottom-width: 2px; padding: 1px 6px; border-radius: 4px; font-size: 11px; font-family: monospace; }
.cylinder-stage { perspective: 1500px; }
.cylinder { transform-style: preserve-3d; animation: cylinderSpin 30s linear infinite; }
@keyframes cylinderSpin { from { transform: rotateY(0); } to { transform: rotateY(360deg); } }
.cursor-dot { position:fixed; width:8px; height:8px; background: var(--primary); border-radius:50%; pointer-events:none; z-index:99999; transform: translate(-50%, -50%); box-shadow: 0 0 20px var(--primary); transition: width .15s, height .15s; }
.cursor-ring { position:fixed; width:36px; height:36px; border:2px solid var(--primary); border-radius:50%; pointer-events:none; z-index:99998; transform: translate(-50%, -50%); transition: transform .15s ease-out, width .2s, height .2s; }
#particleCanvas { position:fixed; inset:0; pointer-events:none; z-index:0; }
.cmd-item:hover, .cmd-item.active { background: var(--primary); color: var(--btn-text); }
#pipOverlay { position:fixed; bottom:100px; right:20px; width:240px; height:135px; border:2px solid var(--primary); border-radius:12px; overflow:hidden; z-index:2500; box-shadow: 0 10px 30px var(--shadow); cursor:move; resize:both; min-width:180px; min-height:100px; }
#pipOverlay video { width:100%; height:100%; object-fit:contain; background:#000; }
.sunburst-arc { transition: opacity .3s; cursor:pointer; }
.sunburst-arc:hover { opacity:0.8; }
.video-timeline { position:relative; height:30px; cursor:pointer; background:rgba(255,255,255,0.1); border-radius:4px; }
.video-timeline .progress { background: var(--primary); height:100%; border-radius:4px; transition: width .1s linear; }
.video-timeline .scrub-thumb { position:absolute; top:-30px; width:80px; height:45px; border:2px solid var(--primary); border-radius:4px; background:#000; object-fit:cover; opacity:0; transition: opacity .2s; transform: translateX(-50%); pointer-events:none; }
.video-timeline:hover .scrub-thumb { opacity:1; }
.video-filter { transition: filter .2s; }
@keyframes kenburns-zoom-in { from {transform:scale(1);} to {transform:scale(1.3);} }
@keyframes kenburns-zoom-out { from {transform:scale(1.3);} to {transform:scale(1);} }
@keyframes kenburns-pan-left { from {transform:scale(1.2) translateX(5%);} to {transform:scale(1.2) translateX(-5%);} }
@keyframes kenburns-pan-right { from {transform:scale(1.2) translateX(-5%);} to {transform:scale(1.2) translateX(5%);} }
@keyframes kenburns-pan-up { from {transform:scale(1.2) translateY(5%);} to {transform:scale(1.2) translateY(-5%);} }
@keyframes kenburns-pan-down { from {transform:scale(1.2) translateY(-5%);} to {transform:scale(1.2) translateY(5%);} }
.masonry-grid { column-count: 4; column-gap: 16px; }
.masonry-grid .item-card { display: inline-block; width: 100%; margin-bottom: 16px; }
@media (max-width: 1024px) { .masonry-grid { column-count: 3; } }
@media (max-width: 768px) { .masonry-grid { column-count: 2; } }
@media (max-width: 480px) { .masonry-grid { column-count: 1; } }
.compact-mode .item-card { height: 130px !important; }
.compact-mode .item-card .text-5xl { font-size: 2rem; }
.context-menu { position:fixed; z-index:6000; background: var(--card); border:1px solid var(--input-border); border-radius:8px; box-shadow: 0 10px 30px var(--shadow); padding:4px; min-width:200px; }
.context-menu-item { padding:8px 12px; cursor:pointer; border-radius:4px; display:flex; align-items:center; gap:8px; font-size:14px; }
.context-menu-item:hover { background: var(--primary); color: var(--btn-text); }
.compare-slider { position:relative; overflow:hidden; }
.compare-slider img { display:block; width:100%; }
.compare-slider .after { position:absolute; top:0; left:0; width:50%; overflow:hidden; }
.compare-slider .after img { width: 200%; max-width: none; }
.compare-slider .handle { position:absolute; top:0; left:50%; width:4px; height:100%; background: var(--primary); cursor: ew-resize; transform: translateX(-50%); }
.compare-slider .handle::after { content:''; position:absolute; top:50%; left:50%; width:32px; height:32px; border-radius:50%; background: var(--primary); transform: translate(-50%, -50%); }
@media (max-width: 640px) { .item-card { height:160px !important; } }
</style>
</head>
<body class="min-h-screen p-3 sm:p-4 lg:p-6 font-sans">
<div class="cursor-dot" id="cursorDot" style="display:none"></div>
<div class="cursor-ring" id="cursorRing" style="display:none"></div>
<canvas id="particleCanvas"></canvas>
<div id="toastContainer" class="fixed top-4 right-4 z-[4000] flex flex-col gap-2 max-w-sm"></div>
<div id="contextMenu" class="context-menu hidden"></div>

<div class="max-w-7xl mx-auto content-layer">
    <div class="glass rounded-2xl p-5 sm:p-6 mb-5 shadow-2xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold flex items-center gap-3 flex-wrap">
                    <i class="fas fa-atom text-primary animate-spin" style="animation-duration:8s;"></i>
                    <span class="bg-clip-text text-transparent" style="background-image: linear-gradient(90deg, var(--primary), var(--accent2), var(--accent3)); -webkit-background-clip: text; background-clip: text;">友達、良い一日を！</span>
                    <span class="text-xs font-normal px-2 py-1 rounded-full bg-primary/15 text-primary uppercase tracking-wider" title="100 Features Edition">ธีม · <?php echo htmlspecialchars($activeTheme); ?></span>
                </h2>
                <div class="mt-2 text-sm text-th-text/60 flex items-center flex-wrap gap-1">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    <a href="index.php" class="breadcrumb-link">/uploads</a>
                    <?php if ($currentFolder !== ''):
                        $parts = explode('/', $currentFolder); $acc = '';
                        foreach ($parts as $idx => $p):
                            $acc .= ($idx === 0 ? '' : '/') . $p;
                            $isLast = ($idx === count($parts) - 1); ?>
                            <span class="text-th-text/40">/</span>
                            <?php if (!$isLast): ?><a href="index.php?folder=<?php echo urlencode($acc); ?>" class="breadcrumb-link"><?php echo htmlspecialchars($p); ?></a>
                            <?php else: ?><span class="text-primary font-semibold"><?php echo htmlspecialchars($p); ?></span><?php endif; ?>
                        <?php endforeach;
                    endif; ?>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <button onclick="openSunburst()" class="px-3 py-1.5 rounded-full bg-accent2/10 text-accent2 font-semibold hover:bg-accent2/20 transition" title="สรุปพื้นที่"><i class="fas fa-chart-pie mr-1"></i><?php echo $totalStorageText; ?></button>
                <span class="px-3 py-1.5 rounded-full bg-primary/10 text-primary font-semibold"><i class="fas fa-folder mr-1"></i><?php echo $folderCount; ?></span>
                <span class="px-3 py-1.5 rounded-full bg-primary/10 text-primary font-semibold"><i class="fas fa-file mr-1"></i><?php echo $fileCount; ?></span>
                <?php if ($imageCount > 0): ?><span class="px-3 py-1.5 rounded-full bg-pink-500/15 text-pink-400 font-semibold"><i class="fas fa-images mr-1"></i><?php echo $imageCount; ?></span><?php endif; ?>
                <?php if ($videoCount > 0): ?><span class="px-3 py-1.5 rounded-full bg-red-500/15 text-red-400 font-semibold"><i class="fas fa-video mr-1"></i><?php echo $videoCount; ?></span><?php endif; ?>
                <?php if ($audioCount > 0): ?><span class="px-3 py-1.5 rounded-full bg-blue-500/15 text-blue-400 font-semibold"><i class="fas fa-music mr-1"></i><?php echo $audioCount; ?></span><?php endif; ?>
                <?php if ($duplicateCount > 0): ?><span class="px-3 py-1.5 rounded-full bg-yellow-500/15 text-yellow-400 font-semibold cursor-pointer" onclick="showDuplicates()" title="ไฟล์ซ้ำ"><i class="fas fa-copy mr-1"></i><?php echo $duplicateCount; ?></span><?php endif; ?>
                <span class="px-3 py-1.5 rounded-full bg-accent3/10 text-accent3 font-semibold cursor-pointer" onclick="openFavorites()" title="รายการโปรด"><i class="fas fa-star mr-1"></i><?php echo count($favorites); ?></span>
                <button onclick="openActivityLog()" class="px-3 py-1.5 rounded-full bg-purple-500/15 text-purple-400 font-semibold" title="Activity Log"><i class="fas fa-history"></i></button>
                <button onclick="openFolderTree()" class="px-3 py-1.5 rounded-full bg-cyan-500/15 text-cyan-400 font-semibold" title="Folder Tree"><i class="fas fa-sitemap"></i></button>
            </div>
        </div>
        <?php if ($message): ?><script>document.addEventListener('DOMContentLoaded', () => showToast(<?php echo json_encode($message); ?>, <?php echo json_encode($messageType); ?>));</script><?php endif; ?>
        <div class="mt-5 flex flex-wrap items-center gap-2">
            <?php if ($currentFolder != ''): ?>
                <a href="index.php?folder=<?php echo urlencode(dirname($currentFolder) === '.' ? '' : dirname($currentFolder)); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-th-text hover:border-primary transition"><i class="fas fa-arrow-left"></i> ย้อนกลับ</a>
                <?php if (isset($folderMeta[$currentFolder]) && !empty($folderMeta[$currentFolder]['locked']) && !empty($_SESSION['unlocked'][$currentFolder])): ?>
                    <form method="POST" class="m-0"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="target_folder" value="<?php echo htmlspecialchars($currentFolder); ?>"><button type="submit" name="relock_folder" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-danger text-white hover:opacity-90 transition"><i class="fas fa-lock"></i> ล็อกอีกครั้ง</button></form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!$isCurrentLocked): ?>
                <form method="POST" class="flex gap-1 m-0">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="text" name="folder_name" placeholder="โฟลเดอร์ใหม่" class="px-4 py-2 rounded-full bg-input-bg border border-input-border text-th-text outline-none focus:border-primary transition" required>
                    <button type="submit" name="new_folder" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-primary text-btn-text hover:opacity-90 transition"><i class="fas fa-folder-plus"></i></button>
                </form>
                <label class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-th-text text-th-dark cursor-pointer hover:opacity-90 transition font-semibold">
                    <i class="fas fa-upload"></i><span class="hidden sm:inline">อัพโหลด</span>
                    <input type="file" name="images[]" id="up" multiple accept="image/*,video/*,audio/*,.pdf,.txt,.json,.zip,.rar,.7z,.doc,.docx,.xls,.xlsx" class="hidden" onchange="handleUpload(this)">
                </label>
                <div id="uploadProgressContainer" class="hidden min-w-[200px] flex-1 max-w-xs">
                    <div class="bg-input-border h-2 rounded-full overflow-hidden"><div id="uploadProgressBar" class="h-full bg-primary transition-all duration-300" style="width:0%"></div></div>
                    <div id="uploadProgressText" class="text-xs text-th-text/70 mt-1 text-center font-semibold">0%</div>
                </div>
                <?php if ($imageCount > 0): ?>
                <button onclick="openImageSlideshow()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-pink-500 text-white hover:opacity-90 transition font-semibold text-sm"><i class="fas fa-images"></i> <span class="hidden md:inline">ภาพสไลด์</span> (<?php echo $imageCount; ?>)</button>
                <button onclick="openCylinderGallery()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-accent2 text-white hover:opacity-90 transition font-semibold text-sm" title="3D Gallery (3)"><i class="fas fa-circle-nodes"></i> <span class="hidden md:inline">3D</span></button>
                <button onclick="openKenBurns()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-purple-500 text-white hover:opacity-90 transition font-semibold text-sm" title="Ken Burns (K)"><i class="fas fa-film"></i> <span class="hidden md:inline">Ken Burns</span></button>
                <button onclick="openMosaic()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-indigo-500 text-white hover:opacity-90 transition font-semibold text-sm" title="Mosaic (O)"><i class="fas fa-th-large"></i> <span class="hidden md:inline">Mosaic</span></button>
                <button onclick="openCompareSlider()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-teal-500 text-white hover:opacity-90 transition font-semibold text-sm" title="เปรียบเทียบภาพ"><i class="fas fa-arrows-left-right"></i> <span class="hidden md:inline">Compare</span></button>
                <?php endif; ?>
                <?php if ($videoCount > 0): ?>
                <button onclick="openVideoPipeline()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-red-500 text-white hover:opacity-90 transition font-semibold text-sm" title="Video Pipeline (V)"><i class="fas fa-film"></i> <span class="hidden md:inline">Video Pipeline</span> (<?php echo $videoCount; ?>)</button>
                <?php endif; ?>
                <?php if ($audioCount > 0): ?>
                <button onclick="openAudioPlayer()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-blue-500 text-white hover:opacity-90 transition font-semibold text-sm" title="Audio Player"><i class="fas fa-headphones"></i> <span class="hidden md:inline">Audio</span> (<?php echo $audioCount; ?>)</button>
                <?php endif; ?>
                <?php if (count($mediaFiles) > 0): ?>
                <button onclick="openSlideshow()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-primary text-btn-text hover:opacity-90 transition font-semibold text-sm"><i class="fas fa-play-circle"></i> <span class="hidden md:inline">มีเดีย</span></button>
                <?php endif; ?>
                <button onclick="openCommandPalette()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm" title="Command Palette (Ctrl+K)"><i class="fas fa-command"></i> <span class="hidden md:inline">Cmd</span> <kbd class="hidden md:inline">⌘K</kbd></button>
                <form method="POST" class="flex gap-1 m-0">
                    <a href="/" class="block py-2.5 px-4 rounded bg-blue-600">กลับ Dashboard</a>
                </form>
                <div class="ml-auto flex items-center gap-2">
                    <select id="sortSelect" onchange="applySort()" class="px-3 py-2 rounded-full bg-input-bg border border-input-border text-th-text outline-none focus:border-primary text-sm">
                        <option value="name-asc">ชื่อ A→Z</option><option value="name-desc">ชื่อ Z→A</option>
                        <option value="size-desc">ขนาด มาก→น้อย</option><option value="size-asc">ขนาด น้อย→มาก</option>
                        <option value="date-desc">ใหม่→เก่า</option><option value="date-asc">เก่า→ใหม่</option>
                        <option value="type-asc">ประเภท</option><option value="rating-desc">คะแนนสูง</option>
                    </select>
                    <select id="viewModeSelect" onchange="setView(this.value)" class="px-3 py-2 rounded-full bg-input-bg border border-input-border text-th-text outline-none focus:border-primary text-sm">
                        <option value="grid">▦ ตาราง</option><option value="list">☰ รายการ</option>
                        <option value="filmstrip">🎞 ฟิล์ม</option><option value="detail">📋 รายละเอียด</option>
                        <option value="masonry">🧱 Masonry</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!$isCurrentLocked && ($folderCount + $fileCount) > 0): ?>
            <div class="mt-4 flex flex-wrap gap-2 items-center">
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-th-text/40"></i>
                    <input type="text" id="searchInput" placeholder="ค้นหาในหน้านี้ (F)..." class="w-full pl-11 pr-4 py-2.5 rounded-full bg-input-bg border border-input-border text-th-text outline-none focus:border-primary transition">
                </div>
                <button onclick="openGlobalSearch()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm"><i class="fas fa-globe"></i> ทั้งหมด</button>
                <button id="selectModeBtn" onclick="toggleSelectMode()" class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm"><i class="fas fa-check-square"></i> เลือกหลาย</button>
                <button onclick="openComparator()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm"><i class="fas fa-columns"></i> เปรียบเทียบ</button>
                <button onclick="toggleZenMode()" class="inline-flex items-center gap-2 px-3 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm" title="Zen Mode (Z)"><i class="fas fa-spa"></i> Zen</button>
                <button onclick="toggleVoiceControl()" id="voiceBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm" title="Voice"><i class="fas fa-microphone"></i> เสียง</button>
                <button onclick="toggleCompactMode()" id="compactBtn" class="inline-flex items-center gap-2 px-3 py-2 rounded-full glass text-th-text hover:border-primary transition text-sm" title="Compact"><i class="fas fa-compress"></i> Compact</button>
                <div id="batchActions" class="hidden items-center gap-2">
                    <span id="selectedCount" class="text-sm text-primary font-semibold">0 ที่เลือก</span>
                    <button onclick="batchMove()" class="px-3 py-2 rounded-full bg-primary text-btn-text hover:opacity-90 transition text-sm"><i class="fas fa-arrows-alt"></i> ย้าย</button>
                    <button onclick="batchDelete()" class="px-3 py-2 rounded-full bg-danger text-white hover:opacity-90 transition text-sm"><i class="fas fa-trash"></i> ลบ</button>
                    <button onclick="batchTag()" class="px-3 py-2 rounded-full bg-accent2 text-white hover:opacity-90 transition text-sm"><i class="fas fa-tag"></i> แท็ก</button>
                    <button onclick="exitSelectMode()" class="px-3 py-2 rounded-full border border-input-border text-th-text hover:border-primary transition text-sm"><i class="fas fa-times"></i> ยกเลิก</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isCurrentLocked): ?>
        <div class="max-w-md mx-auto mt-16 text-center glass rounded-2xl p-8 shadow-2xl">
            <i class="fas fa-lock text-5xl text-danger mb-4"></i>
            <h3 class="text-xl font-bold mb-2">โฟลเดอร์นี้ถูกล็อก</h3>
            <p class="text-th-text/60 text-sm mb-5">กรุณากรอกรหัสผ่าน</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="target_folder" value="<?php echo htmlspecialchars($requireUnlockPath); ?>">
                <input type="password" name="folder_password" placeholder="รหัสผ่าน" required class="w-full px-4 py-3 rounded-xl bg-input-bg border border-input-border text-th-text outline-none focus:border-primary transition mb-4">
                <button type="submit" name="unlock_folder" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-primary text-btn-text font-bold hover:opacity-90 transition"><i class="fas fa-unlock"></i> ปลดล็อก</button>
            </form>
        </div>
    <?php else: ?>
        <?php if (!empty($recentFiles) && $currentFolder === ''): ?>
            <div class="mb-6 content-layer">
                <h3 class="text-sm font-bold text-th-text/70 uppercase tracking-wider mb-3 flex items-center gap-2"><i class="fas fa-clock text-accent2"></i> เพิ่งเพิ่มล่าสุด (7 วัน)</h3>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <?php foreach ($recentFiles as $rf): ?>
                        <div onclick="handleFileClick('<?php echo htmlspecialchars($rf['path'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($rf['type'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($rf['name'], ENT_QUOTES); ?>')" class="flex-shrink-0 w-32 h-32 rounded-xl glass overflow-hidden cursor-pointer hover:border-primary transition group relative">
                            <?php if ($rf['type'] === 'image'): ?><img src="<?php echo htmlspecialchars($rf['path']); ?>" class="w-full h-full object-cover" loading="lazy">
                            <?php elseif ($rf['type'] === 'video'): ?><video src="<?php echo htmlspecialchars($rf['path']); ?>" class="w-full h-full object-cover" muted preload="metadata"></video><div class="absolute inset-0 flex items-center justify-center"><i class="fas fa-play-circle text-3xl text-white/80"></i></div>
                            <?php else: ?><div class="w-full h-full flex flex-col items-center justify-center"><i class="fas <?php echo fileTypeIcon($rf['type']); ?> text-3xl mb-2"></i><span class="text-xs px-2 text-center truncate w-full"><?php echo htmlspecialchars($rf['name']); ?></span></div><?php endif; ?>
                            <div class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-black/70 text-white text-xs truncate"><?php echo htmlspecialchars($rf['name']); ?></div>
                            <?php if ($rf['rating'] > 0): ?><div class="absolute top-1 right-1 text-yellow-400 text-xs"><?php echo str_repeat('★', $rf['rating']); ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($topRated) && $currentFolder === ''): ?>
            <div class="mb-6 content-layer">
                <h3 class="text-sm font-bold text-th-text/70 uppercase tracking-wider mb-3 flex items-center gap-2"><i class="fas fa-star text-yellow-400"></i> คะแนนสูงสุด</h3>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <?php foreach ($topRated as $tr): ?>
                        <div onclick="handleFileClick('<?php echo htmlspecialchars($tr['path'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($tr['type'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($tr['name'], ENT_QUOTES); ?>')" class="flex-shrink-0 w-32 h-32 rounded-xl glass overflow-hidden cursor-pointer hover:border-primary transition group relative">
                            <?php if ($tr['type'] === 'image'): ?><img src="<?php echo htmlspecialchars($tr['path']); ?>" class="w-full h-full object-cover" loading="lazy">
                            <?php else: ?><div class="w-full h-full flex flex-col items-center justify-center"><i class="fas <?php echo fileTypeIcon($tr['type']); ?> text-3xl mb-2"></i><span class="text-xs px-2 text-center truncate w-full"><?php echo htmlspecialchars($tr['name']); ?></span></div><?php endif; ?>
                            <div class="absolute top-1 right-1 text-yellow-400 text-xs"><?php echo str_repeat('★', $tr['rating']); ?></div>
                            <div class="absolute bottom-0 left-0 right-0 px-2 py-1 bg-black/70 text-white text-xs truncate"><?php echo htmlspecialchars($tr['name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div id="gridContainer" class="grid gap-4 content-layer" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
            <?php foreach ($folders as $item):
                $relPath = ($currentFolder ? $currentFolder . '/' : '') . $item;
                $meta = $folderMeta[$relPath] ?? ['locked' => false, 'cover' => ''];
                $bgImage = !empty($meta['cover']) ? "imgfd/" . rawurlencode($meta['cover']) : '';
                $isLocked = !empty($meta['locked']);
                $imgList = $folderImagesMap[$item] ?? [];
                $vidList = $folderVideosMap[$item] ?? [];
                $basePath = $folderMediaPathMap[$item] ?? '';
                $imgCount = count($imgList); $vidCount = count($vidList);
                $totalMedia = $imgCount + $vidCount;
                $folderSizeText = formatFileSize($folderSizes[$item] ?? 0);
                ?>
                <div class="item-card group relative rounded-2xl overflow-hidden border border-input-border transition-all duration-300 hover:border-primary hover:-translate-y-1 cursor-pointer"
                     style="background: var(--item-bg); height: 200px;"
                     data-name="<?php echo htmlspecialchars($item); ?>" data-type="folder" data-path="<?php echo htmlspecialchars($relPath); ?>"
                     onclick="event.preventDefault(); window.location.href='?folder=<?php echo urlencode($relPath); ?>'">
                    <?php if (empty($bgImage) && $imgCount > 0 && $vidCount === 0):
                        $previewImgs = array_slice($imgList, 0, 8);
                        foreach ($previewImgs as $idx => $imgName): ?>
                            <div class="folder-preview-img <?php echo $idx === 0 ? 'active' : ''; ?>" data-folder="<?php echo htmlspecialchars($item); ?>" style="background-image: url('<?php echo htmlspecialchars($basePath . rawurlencode($imgName)); ?>');"></div>
                        <?php endforeach;
                    elseif (!empty($bgImage)): ?>
                        <div class="folder-preview-img active" style="background-image: url('<?php echo htmlspecialchars($bgImage); ?>');"></div>
                    <?php endif; ?>
                    <?php if ($vidCount > 0 && empty($bgImage)):
                        $firstVid = $vidList[0]; ?>
                        <video class="folder-preview-video active" data-folder="<?php echo htmlspecialchars($item); ?>" src="<?php echo htmlspecialchars($basePath . rawurlencode($firstVid)); ?>" muted loop playsinline preload="metadata" autoplay></video>
                    <?php endif; ?>
                    <div class="absolute top-2 right-2 z-20 flex gap-1 opacity-0 group-hover:opacity-100 transition glass p-1 rounded-lg" onclick="event.stopPropagation(); event.preventDefault();">
                        <button class="p-1.5 text-th-text/70 hover:text-primary transition" title="ตั้งค่า" onclick="openFolderMeta('<?php echo htmlspecialchars($item, ENT_QUOTES); ?>', <?php echo $isLocked ? 'true' : 'false'; ?>)"><i class="fas fa-cog"></i></button>
                        <button class="p-1.5 text-th-text/70 hover:text-primary transition" title="ย้าย" onclick="doMove('<?php echo htmlspecialchars($item, ENT_QUOTES); ?>')"><i class="fas fa-arrows-alt"></i></button>
                        <button class="p-1.5 text-th-text/70 hover:text-danger transition" title="ลบ" onclick="doDelete('<?php echo htmlspecialchars($item, ENT_QUOTES); ?>')"><i class="fas fa-trash"></i></button>
                    </div>
                    <button class="select-btn hidden absolute top-2 left-2 z-20 w-7 h-7 rounded-md border-2 border-input-border glass items-center justify-center" onclick="event.stopPropagation(); event.preventDefault(); toggleSelectItem(this, '<?php echo htmlspecialchars($item, ENT_QUOTES); ?>')"><i class="fas fa-check text-primary text-sm opacity-0"></i></button>
                    <?php if ($isLocked): ?><div class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full bg-danger/80 text-white text-xs flex items-center gap-1"><i class="fas fa-lock"></i> ล็อก</div>
                    <?php elseif ($totalMedia > 0): ?>
                        <div class="absolute top-2 left-2 z-10 flex gap-1">
                            <?php if ($vidCount > 0): ?><span class="px-2 py-1 rounded-full bg-red-500/80 text-white text-xs flex items-center gap-1"><i class="fas fa-video"></i> <?php echo $vidCount; ?></span><?php endif; ?>
                            <?php if ($imgCount > 0): ?><span class="px-2 py-1 rounded-full bg-pink-500/80 text-white text-xs flex items-center gap-1"><i class="fas fa-images"></i> <?php echo $imgCount; ?></span><?php endif; ?>
                            <?php if ($imgCount > 1 && $vidCount === 0): ?><span class="px-2 py-1 rounded-full bg-black/60 text-primary text-xs"><i class="fas fa-sync-alt"></i></span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <a href="?folder=<?php echo urlencode($relPath); ?>" class="flex flex-col items-center justify-center w-full h-full no-underline <?php echo ($bgImage || $totalMedia > 0) ? 'bg-black/30' : ''; ?>" onclick="event.preventDefault();">
                        <?php if (!$bgImage && $totalMedia === 0): ?><i class="<?php echo $isLocked ? 'fas fa-lock text-danger' : 'fas fa-folder text-yellow-400'; ?> text-5xl mb-2"></i><?php endif; ?>
                        <div class="absolute bottom-0 left-0 right-0 px-2 py-2 bg-black/70 backdrop-blur-sm text-white text-xs sm:text-sm text-center font-medium break-words">
                            <?php echo htmlspecialchars($item); ?>
                            <div class="text-[10px] text-white/60"><?php echo $folderSizeText; ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
            <?php foreach ($filesList as $file): ?>
                <div class="item-card group relative rounded-2xl overflow-hidden border border-input-border transition-all duration-300 hover:border-primary hover:-translate-y-1"
                     style="background: var(--item-bg); height: 200px;"
                     data-name="<?php echo htmlspecialchars($file['name']); ?>" data-type="<?php echo htmlspecialchars($file['type']); ?>" data-size="<?php echo $file['size']; ?>" data-date="<?php echo $file['mtime']; ?>" data-path="<?php echo htmlspecialchars($file['path']); ?>" data-tag="<?php echo htmlspecialchars($file['tag']); ?>" data-rating="<?php echo $file['rating']; ?>"
                     oncontextmenu="showContextMenu(event, <?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>); return false;">
                    <div class="absolute top-2 right-2 z-20 flex gap-1 opacity-0 group-hover:opacity-100 transition glass p-1 rounded-lg">
                        <?php if ($file['tag'] === 'favorite'): ?><button class="p-1.5 text-yellow-400" title="ยกเลิกโปรด" onclick="toggleFavorite('<?php echo htmlspecialchars($file['relPath'], ENT_QUOTES); ?>', '')"><i class="fas fa-star"></i></button>
                        <?php else: ?><button class="p-1.5 text-th-text/70 hover:text-yellow-400 transition" title="เพิ่มโปรด" onclick="toggleFavorite('<?php echo htmlspecialchars($file['relPath'], ENT_QUOTES); ?>', 'favorite')"><i class="far fa-star"></i></button><?php endif; ?>
                        <button class="p-1.5 text-th-text/70 hover:text-primary transition" title="ข้อมูล" onclick="showFileInfo(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)"><i class="fas fa-info-circle"></i></button>
                        <button class="p-1.5 text-th-text/70 hover:text-primary transition" title="เปลี่ยนชื่อ" onclick="doRename('<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>')"><i class="fas fa-edit"></i></button>
                        <button class="p-1.5 text-th-text/70 hover:text-primary transition" title="ย้าย" onclick="doMove('<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>')"><i class="fas fa-arrows-alt"></i></button>
                        <button class="p-1.5 text-th-text/70 hover:text-danger transition" title="ลบ" onclick="doDelete('<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>')"><i class="fas fa-trash"></i></button>
                    </div>
                    <button class="select-btn hidden absolute top-2 left-2 z-20 w-7 h-7 rounded-md border-2 border-input-border glass items-center justify-center" onclick="event.stopPropagation(); toggleSelectItem(this, '<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>')"><i class="fas fa-check text-primary text-sm opacity-0"></i></button>
                    <div class="absolute top-2 left-2 z-10 px-2 py-1 rounded-full text-xs font-semibold <?php echo fileTypeColor($file['type']); ?>"><?php echo strtoupper($file['ext'] ?: 'FILE'); ?></div>
                    <?php if ($file['tag'] === 'favorite'): ?><div class="absolute bottom-9 left-2 z-10 text-yellow-400 text-sm"><i class="fas fa-star"></i></div><?php endif; ?>
                    <?php if ($file['rating'] > 0): ?><div class="absolute bottom-9 right-2 z-10 text-yellow-400 text-xs"><?php echo str_repeat('★', $file['rating']); ?></div><?php endif; ?>
                    <div class="flex flex-col items-center justify-center w-full h-full cursor-pointer" onclick="handleFileClick('<?php echo htmlspecialchars($file['path'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($file['type'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($file['name'], ENT_QUOTES); ?>')">
                        <?php if ($file['type'] === 'image'): ?><img src="<?php echo htmlspecialchars($file['path']); ?>" alt="<?php echo htmlspecialchars($file['name']); ?>" class="w-full h-full object-cover" loading="lazy">
                        <?php elseif ($file['type'] === 'video'): ?>
                            <video src="<?php echo htmlspecialchars($file['path']); ?>" class="w-full h-full object-cover" muted preload="metadata"></video>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none"><div class="w-12 h-12 rounded-full bg-black/60 flex items-center justify-center"><i class="fas fa-play text-white text-xl"></i></div></div>
                        <?php else: ?><i class="fas <?php echo fileTypeIcon($file['type']); ?> text-5xl mb-2"></i><div class="text-xs text-th-text/60 mt-1"><?php echo $file['sizeText']; ?></div><?php endif; ?>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 px-2 py-2 bg-black/70 backdrop-blur-sm text-white text-xs sm:text-sm text-center font-medium break-words"><?php echo htmlspecialchars($file['name']); ?></div>
                </div>
            <?php endforeach; ?>
            <?php if ($folderCount === 0 && $fileCount === 0): ?>
                <div class="col-span-full text-center py-16 text-th-text/50">
                    <i class="fas fa-cloud-upload-alt text-6xl mb-4 text-primary/50"></i>
                    <p class="text-lg">โฟลเดอร์นี้ว่างเปล่า</p>
                    <p class="text-sm mt-2">คลิกปุ่ม "อัพโหลด" หรือลากไฟล์มาวาง</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- FILE INFO MODAL -->
<div id="fileInfoModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-md border-2 border-primary shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-primary flex items-center gap-2"><i class="fas fa-info-circle"></i> ข้อมูลไฟล์</h3>
            <button onclick="closeModal('fileInfoModal')" class="text-th-text/60 hover:text-danger transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div id="fileInfoBody" class="space-y-3 text-sm"></div>
    </div>
</div>

<!-- MOVE MODAL -->
<div id="moveModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-md border-2 border-primary shadow-2xl">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2"><i class="fas fa-arrows-alt"></i> ย้ายไฟล์/โฟลเดอร์</h3>
        <div class="mb-4">
            <label class="block text-xs font-bold text-primary mb-1">เลือกโฟลเดอร์ปลายทาง</label>
            <select id="moveTargetSelect" class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text outline-none focus:border-primary">
                <?php foreach ($allAvailableFolders as $f): ?><option value="<?php echo htmlspecialchars($f); ?>"><?php echo $f === '' ? '/ (หน้าแรก)' : '/' . htmlspecialchars($f); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="button" class="flex-1 px-4 py-2 rounded-full bg-primary text-btn-text font-semibold hover:opacity-90 transition" onclick="confirmMove()">ย้าย</button>
            <button type="button" class="flex-1 px-4 py-2 rounded-full border border-input-border text-th-text hover:border-primary transition" onclick="closeModal('moveModal')">ยกเลิก</button>
        </div>
    </div>
</div>

<!-- FOLDER META MODAL -->
<div id="folderMetaModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-md border-2 border-primary shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2"><i class="fas fa-cog"></i> ตั้งค่าโฟลเดอร์</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action_type" value="edit_folder_meta">
            <input type="hidden" name="target_folder" id="metaTargetFolder">
            <div class="mb-4"><label class="block text-xs font-bold text-primary mb-1">ชื่อโฟลเดอร์ใหม่</label><input type="text" name="new_folder_name" id="metaNewName" required class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text outline-none focus:border-primary"></div>
            <div class="mb-4"><label class="flex items-center gap-2 cursor-pointer text-sm"><input type="checkbox" name="is_locked" id="metaIsLocked" class="w-4 h-4 rounded accent-primary" onchange="togglePasswordField()"><i class="fas fa-lock text-danger"></i> ล็อกด้วยรหัสผ่าน</label></div>
            <div id="passwordFields" class="hidden mb-4 space-y-3">
                <div><label class="block text-xs font-bold text-primary mb-1">รหัสผ่านปัจจุบัน</label><input type="password" name="current_password" placeholder="เว้นว่างถ้าไม่ได้ล็อก" class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text outline-none focus:border-primary"></div>
                <div><label class="block text-xs font-bold text-primary mb-1">รหัสผ่านใหม่ (เว้นว่างเพื่อใช้ของเดิม)</label><input type="password" name="folder_password" class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text outline-none focus:border-primary"></div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-bold text-primary mb-2">ภาพปก (ว่าง = สลับอัตโนมัติ)</label>
                <input type="hidden" name="folder_cover_select" id="metaCoverSelectHidden" value="">
                <div id="metaCoverGrid" class="grid gap-2 max-h-48 overflow-y-auto p-2 rounded-lg bg-input-bg border border-input-border" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));"></div>
            </div>
            <div class="flex gap-2 mt-5">
                <button type="submit" class="flex-1 px-4 py-2 rounded-full bg-primary text-btn-text font-semibold hover:opacity-90 transition"><i class="fas fa-save mr-1"></i> บันทึก</button>
                <button type="button" class="flex-1 px-4 py-2 rounded-full border border-input-border text-th-text hover:border-primary transition" onclick="closeModal('folderMetaModal')">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<div id="mainModal" class="hidden fixed inset-0 z-[2000]">
    <button class="toggle-ui-standalone absolute top-5 right-5 z-[2300] w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary" onclick="toggleUI()" title="ซ่อน/แสดง UI (H)"><i class="fas fa-eye-slash"></i></button>
    <div class="top-panel absolute top-5 right-20 z-[2200] flex gap-2 transition-all duration-300">
        <button id="btnOpenFull" title="เปิดหน้าใหม่" class="w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary"><i class="fas fa-external-link-alt"></i></button>
        <button id="btnDownload" title="ดาวน์โหลด" class="w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary"><i class="fas fa-download"></i></button>
        <button id="btnScreenshot" title="บันทึกภาพ" class="hidden w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary" onclick="screenshotFrame()"><i class="fas fa-camera"></i></button>
        <button id="btnFullscreen" title="เต็มจอ (F)" class="hidden w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary" onclick="toggleFullscreen()"><i class="fas fa-expand"></i></button>
        <button id="btnPip" title="PiP" class="hidden w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary" onclick="togglePip()"><i class="fas fa-clone"></i></button>
        <button id="btnTheater" title="Theater Mode (T)" class="hidden w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary" onclick="toggleTheater()"><i class="fas fa-film"></i></button>
        <button id="btnMini" title="Mini Player" class="hidden w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-primary hover:text-primary" onclick="toggleMini()"><i class="fas fa-window-restore"></i></button>
        <button onclick="closePlayer()" title="ปิด (Esc)" class="w-11 h-11 rounded-full glass flex items-center justify-center transition hover:border-danger hover:text-danger"><i class="fas fa-times"></i></button>
    </div>
    <div class="swiper"><div class="swiper-wrapper" id="swiperWrapper"></div><div class="swiper-button-next" style="color: var(--primary);"></div><div class="swiper-button-prev" style="color: var(--primary);"></div></div>
    <div class="bottom-panel absolute bottom-6 left-1/2 -translate-x-1/2 z-[2100] flex flex-col items-center gap-3 transition-all duration-300 pointer-events-none w-full px-4">
        <div id="speedControlGroup" class="hidden control-group pointer-events-auto w-full max-w-md px-5 py-3 rounded-2xl glass">
            <div class="flex items-center justify-between mb-2"><span class="text-xs font-bold text-th-text/70">ความเร็วสไลด์</span><span id="speedValueLabel" class="text-sm font-bold text-primary">3.0 วิ/ภาพ</span></div>
            <div class="flex items-center gap-3"><span class="text-xs text-th-text/50">0.5s</span><input type="range" id="speedSlider" min="500" max="30000" step="500" value="3000" class="flex-1" oninput="updateSpeedFromSlider(this.value)"><span class="text-xs text-th-text/50">30s</span></div>
        </div>
        <div id="videoControlsGroup" class="hidden control-group pointer-events-auto w-full max-w-2xl px-5 py-3 rounded-2xl glass space-y-3">
            <div class="video-timeline" id="videoTimeline" onclick="seekVideo(event)"><div class="progress" id="videoProgress" style="width:0%"></div><video class="scrub-thumb" id="scrubThumb" muted></video></div>
            <div class="flex items-center justify-between text-xs text-th-text/70"><span id="videoTimeCurrent">0:00</span><span id="videoTimeDuration">0:00</span></div>
            <div class="flex flex-wrap items-center gap-1 justify-center">
                <button onclick="videoSeek(-10)" title="-10s (J)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-backward"></i> 10s</button>
                <button onclick="videoSeek(-5)" title="-5s (←)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-backward"></i> 5s</button>
                <button onclick="videoSeek(-1)" title="-1s" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-step-backward"></i></button>
                <button onclick="videoFrameStep(-1)" title="-1 frame (,)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-angle-left"></i></button>
                <button onclick="videoFrameStep(1)" title="+1 frame (.)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-angle-right"></i></button>
                <button onclick="videoSeek(1)" title="+1s" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-step-forward"></i></button>
                <button onclick="videoSeek(5)" title="+5s (→)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs">5s <i class="fas fa-forward"></i></button>
                <button onclick="videoSeek(10)" title="+10s (L)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs">10s <i class="fas fa-forward"></i></button>
                <button onclick="toggleABLoop()" title="ตั้งจุด A-B" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-repeat"></i> A-B</button>
                <button onclick="bookmarkFrame()" title="บันทึก bookmark" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-bookmark"></i></button>
                <button onclick="screenshotFrame()" title="บันทึกภาพ" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-camera"></i></button>
                <button onclick="reverseVideo()" title="เล่นกลับ" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-backward-fast"></i> Rev</button>
                <button onclick="slowMoPreset()" title="Slow-mo (Shift+S)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-gauge-simple"></i> Slow</button>
                <button onclick="videoAudioOnly()" title="โหมดเสียงอย่างเดียว" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-headphones"></i> Audio</button>
                <select onchange="setVideoSpeed(parseFloat(this.value))" class="px-2 py-1 rounded bg-input-bg border border-input-border text-th-text text-xs">
                    <option value="0.25">0.25x</option><option value="0.5">0.5x</option><option value="1" selected>1x</option><option value="1.5">1.5x</option><option value="2">2x</option><option value="4">4x</option>
                </select>
                <button onclick="addChapter()" title="เพิ่ม chapter" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-flag"></i></button>
            </div>
            <div class="grid grid-cols-3 gap-2 text-xs">
                <div><label class="text-th-text/60">Hue <span id="hueVal">0</span>°</label><input type="range" min="0" max="360" value="0" class="w-full" oninput="setVideoFilter('hue', this.value)"></div>
                <div><label class="text-th-text/60">Sat <span id="satVal">100</span>%</label><input type="range" min="0" max="200" value="100" class="w-full" oninput="setVideoFilter('sat', this.value)"></div>
                <div><label class="text-th-text/60">Bright <span id="briVal">100</span>%</label><input type="range" min="0" max="200" value="100" class="w-full" oninput="setVideoFilter('bri', this.value)"></div>
            </div>
            <div class="flex gap-2 justify-center flex-wrap">
                <button onclick="videoTransform('flipX')" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-arrows-left-right"></i> พลิก</button>
                <button onclick="videoTransform('flipY')" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-arrows-up-down"></i> กลับ</button>
                <button onclick="videoTransform('rotate')" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-rotate"></i> หมุน</button>
                <button onclick="digitalZoom()" title="ซูมดิจิทัล (Z)" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-magnifying-glass-plus"></i> ซูม</button>
                <button onclick="resetVideoFilters()" class="px-2 py-1 rounded bg-input-bg border border-input-border hover:border-primary text-xs"><i class="fas fa-undo"></i> รีเซ็ต</button>
            </div>
            <div id="chaptersList" class="hidden flex flex-wrap gap-1"></div>
        </div>
        <div id="audioVisualizer" class="hidden control-group pointer-events-auto w-full max-w-md px-5 py-3 rounded-2xl glass">
            <div class="flex items-center justify-between mb-2"><span class="text-xs font-bold text-th-text/70">Visualizer</span><select onchange="setEqPreset(this.value)" class="text-xs bg-input-bg border border-input-border rounded px-2 py-1"><option value="flat">Flat</option><option value="bass">Bass Boost</option><option value="treble">Treble</option><option value="vocal">Vocal</option></select></div>
            <canvas id="vizCanvas" width="400" height="60" class="w-full"></canvas>
        </div>
        <div class="flex flex-wrap justify-center gap-3 pointer-events-auto">
            <div class="control-group flex gap-2 px-4 py-2.5 rounded-2xl glass">
                <button class="ratio-btn active px-3 py-2 rounded-lg bg-primary text-btn-text border border-primary text-sm font-bold transition" onclick="setRatio('contain', this)">Fit</button>
                <button class="ratio-btn px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text text-sm font-bold hover:border-primary transition" onclick="setRatio('cover', this)">Fill</button>
            </div>
            <div id="slideshowControls" class="control-group hidden gap-2 px-4 py-2.5 rounded-2xl glass">
                <button id="shuffleBtn" class="ctrl-btn px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text text-sm font-bold hover:border-primary transition" onclick="toggleShuffle()" title="สุ่ม"><i class="fas fa-shuffle"></i></button>
                <button id="loopModeBtn" class="ctrl-btn px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text text-sm font-bold hover:border-primary transition" onclick="toggleLoopMode()"><i class="fas fa-sync mr-1"></i> วนทั้งหมด</button>
                <button id="autoPlayBtn" class="ctrl-btn primary px-3 py-2 rounded-lg bg-primary text-btn-text border border-primary text-sm font-bold hover:opacity-90 transition" onclick="toggleAutoplay()"><i class="fas fa-play mr-1"></i> เล่นสไลด์</button>
            </div>
        </div>
    </div>
</div>

<div id="pipOverlay" class="hidden">
    <video id="pipVideo" controls autoplay loop muted></video>
    <div class="absolute top-1 right-1 flex gap-1"><button onclick="closePip()" class="w-6 h-6 rounded-full bg-black/70 text-white text-xs"><i class="fas fa-times"></i></button></div>
</div>

<div id="commandPalette" class="hidden fixed inset-0 z-[5000] flex items-start justify-center pt-24 p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl w-full max-w-xl border-2 border-primary shadow-2xl overflow-hidden">
        <div class="flex items-center gap-3 p-4 border-b border-input-border">
            <i class="fas fa-command text-primary"></i>
            <input type="text" id="cmdInput" placeholder="พิมพ์คำสั่งหรือค้นหา..." class="flex-1 bg-transparent outline-none text-th-text text-lg" oninput="filterCommands()" autofocus>
            <kbd>Esc</kbd>
        </div>
        <div id="cmdResults" class="max-h-80 overflow-y-auto p-2"></div>
    </div>
</div>

<div id="cylinderModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center" style="background:#000;">
    <button onclick="document.getElementById('cylinderModal').classList.add('hidden')" class="absolute top-5 right-5 z-10 w-11 h-11 rounded-full glass flex items-center justify-center text-white hover:text-red-400 transition"><i class="fas fa-times text-xl"></i></button>
    <div class="cylinder-stage w-full h-full flex items-center justify-center overflow-hidden"><div id="cylinderInner" class="cylinder relative" style="width:200px; height:280px;"></div></div>
    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 text-white/70 text-sm">เลื่อนเมาส์เพื่อหมุน · คลิกที่รูปเพื่อขยาย</div>
</div>

<div id="mosaicModal" class="hidden fixed inset-0 z-[3000] overflow-y-auto p-4" style="background:#000;">
    <button onclick="document.getElementById('mosaicModal').classList.add('hidden')" class="fixed top-5 right-5 z-10 w-11 h-11 rounded-full glass flex items-center justify-center text-white hover:text-red-400 transition"><i class="fas fa-times text-xl"></i></button>
    <div id="mosaicGrid" class="grid gap-1 max-w-7xl mx-auto mt-16" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));"></div>
</div>

<div id="sunburstModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-lg border-2 border-primary shadow-2xl">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-primary"><i class="fas fa-chart-pie"></i> สรุปพื้นที่จัดเก็บ</h3><button onclick="document.getElementById('sunburstModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button></div>
        <svg id="sunburstSvg" viewBox="0 0 300 300" class="w-full"></svg>
        <div id="sunburstLegend" class="mt-4 grid grid-cols-2 gap-2 text-xs"></div>
    </div>
</div>

<div id="globalSearchModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-2xl border-2 border-primary shadow-2xl max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-primary"><i class="fas fa-globe"></i> ค้นหาทั้งหมด</h3><button onclick="document.getElementById('globalSearchModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button></div>
        <input type="text" id="globalSearchInput" placeholder="พิมพ์เพื่อค้นหาในทุกโฟลเดอร์..." class="w-full px-4 py-3 rounded-xl bg-input-bg border border-input-border text-th-text outline-none focus:border-primary mb-4">
        <div id="globalSearchResults" class="overflow-y-auto flex-1 space-y-2"></div>
    </div>
</div>

<div id="comparatorModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-5xl border-2 border-primary shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-primary"><i class="fas fa-columns"></i> เปรียบเทียบไฟล์</h3><button onclick="document.getElementById('comparatorModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button></div>
        <div class="grid grid-cols-2 gap-4">
            <div><select id="compareA" class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text mb-2" onchange="updateCompare()"><?php foreach ($filesList as $f): ?><option value="<?php echo htmlspecialchars($f['path']); ?>" data-type="<?php echo $f['type']; ?>"><?php echo htmlspecialchars($f['name']); ?></option><?php endforeach; ?></select><div id="compareViewA" class="aspect-video bg-black rounded-lg overflow-hidden"></div></div>
            <div><select id="compareB" class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text mb-2" onchange="updateCompare()"><?php foreach ($filesList as $f): ?><option value="<?php echo htmlspecialchars($f['path']); ?>" data-type="<?php echo $f['type']; ?>"><?php echo htmlspecialchars($f['name']); ?></option><?php endforeach; ?></select><div id="compareViewB" class="aspect-video bg-black rounded-lg overflow-hidden"></div></div>
        </div>
    </div>
</div>

<div id="favoritesModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-2xl border-2 border-primary shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-yellow-400"><i class="fas fa-star"></i> รายการโปรด</h3><button onclick="document.getElementById('favoritesModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button></div>
        <div id="favoritesList" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <?php if (empty($favorites)): ?><div class="col-span-full text-center py-8 text-th-text/50">ยังไม่มีรายการโปรด คลิก <i class="far fa-star"></i> ที่ไฟล์เพื่อเพิ่ม</div>
            <?php else: foreach ($favorites as $fav): ?>
                <div onclick="handleFileClick('<?php echo htmlspecialchars($fav['path'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($fav['type'], ENT_QUOTES); ?>','<?php echo htmlspecialchars($fav['name'], ENT_QUOTES); ?>'); document.getElementById('favoritesModal').classList.add('hidden');" class="cursor-pointer rounded-xl overflow-hidden border border-input-border hover:border-yellow-400 transition">
                    <?php if ($fav['type'] === 'image'): ?><img src="<?php echo htmlspecialchars($fav['path']); ?>" class="w-full h-32 object-cover">
                    <?php else: ?><div class="w-full h-32 flex items-center justify-center"><i class="fas <?php echo fileTypeIcon($fav['type']); ?> text-3xl"></i></div><?php endif; ?>
                    <div class="p-2 text-xs truncate"><?php echo htmlspecialchars($fav['name']); ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div id="folderTreeModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-md border-2 border-primary shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-cyan-400"><i class="fas fa-sitemap"></i> โครงสร้างโฟลเดอร์</h3><button onclick="document.getElementById('folderTreeModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button></div>
        <div id="folderTreeContent" class="text-sm">
            <?php
            function renderTree($folders, $currentFolder) {
                echo '<ul class="ml-4 border-l border-input-border pl-2">';
                foreach ($folders as $f) {
                    if ($f === '') { echo '<li><a href="index.php" class="hover:text-primary">🏠 หน้าแรก</a></li>'; continue; }
                    echo '<li><a href="index.php?folder=' . urlencode($f) . '" class="hover:text-primary">📁 ' . htmlspecialchars($f) . '</a></li>';
                }
                echo '</ul>';
            }
            renderTree($allAvailableFolders, $currentFolder);
            ?>
        </div>
    </div>
</div>

<div id="activityModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-lg border-2 border-purple-400 shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4"><h3 class="text-lg font-bold text-purple-400"><i class="fas fa-history"></i> Activity Log</h3><button onclick="document.getElementById('activityModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button></div>
        <div class="space-y-2 text-sm">
            <?php if (empty($recentActivity)): ?><div class="text-center text-th-text/50 py-4">ยังไม่มีกิจกรรม</div>
            <?php else: foreach ($recentActivity as $act): ?>
                <div class="flex items-center gap-3 p-2 rounded-lg bg-input-bg">
                    <i class="fas <?php
                        $ic = ['delete'=>'fa-trash text-red-400','rename'=>'fa-edit text-blue-400','move'=>'fa-arrows-alt text-yellow-400','upload'=>'fa-upload text-green-400','mkdir'=>'fa-folder-plus text-purple-400','tag'=>'fa-tag text-pink-400','batch_delete'=>'fa-trash-can text-red-500','batch_move'=>'fa-boxes-packing text-yellow-500'];
                        echo $ic[$act['action']] ?? 'fa-circle text-gray-400';
                    ?>"></i>
                    <div class="flex-1"><div class="font-semibold"><?php echo htmlspecialchars($act['action']); ?></div><div class="text-xs text-th-text/60"><?php echo htmlspecialchars($act['item']); ?></div></div>
                    <div class="text-xs text-th-text/50"><?php echo date('H:i', $act['time']); ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div id="compareSliderModal" class="hidden fixed inset-0 z-[3000] flex items-center justify-center p-4" style="background: var(--overlay); backdrop-filter: blur(5px);">
    <div class="glass rounded-2xl p-6 w-full max-w-3xl border-2 border-teal-400 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-teal-400"><i class="fas fa-arrows-left-right"></i> เปรียบเทียบภาพ (Slider)</h3>
            <button onclick="document.getElementById('compareSliderModal').classList.add('hidden')" class="text-th-text/60 hover:text-danger"><i class="fas fa-times"></i></button>
        </div>
        <div class="grid grid-cols-2 gap-2 mb-3">
            <select id="cmpSliderA" class="px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text" onchange="updateCompareSlider()"><?php foreach ($imagesOnly as $f): ?><option value="<?php echo htmlspecialchars($f['path']); ?>"><?php echo htmlspecialchars($f['name']); ?></option><?php endforeach; ?></select>
            <select id="cmpSliderB" class="px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text" onchange="updateCompareSlider()"><?php foreach ($imagesOnly as $f): ?><option value="<?php echo htmlspecialchars($f['path']); ?>"><?php echo htmlspecialchars($f['name']); ?></option><?php endforeach; ?></select>
        </div>
        <div id="compareSliderContainer" class="compare-slider rounded-xl overflow-hidden bg-black"></div>
    </div>
</div>

<div class="fixed bottom-5 right-5 z-[9999] flex items-center gap-2 px-4 py-2.5 rounded-full glass shadow-2xl">
    <i class="fas fa-palette text-primary"></i>
    <select onchange="window.location.href='?folder=<?php echo urlencode($currentFolder); ?>&set_theme='+this.value" class="bg-transparent text-th-text border-none outline-none cursor-pointer font-bold appearance-none pr-1">
        <?php foreach (['quantum','master','dark','vspo','icloud','vapor','cyber','matrix'] as $t): ?>
            <option value="<?php echo $t; ?>" <?php if($activeTheme===$t) echo 'selected'; ?> style="color:#000;"><?php echo ucfirst($t); ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div id="shortcutsHelp" class="hidden fixed bottom-5 left-5 z-[9999] max-w-md rounded-2xl glass shadow-2xl p-4 max-h-[80vh] overflow-y-auto">
    <h4 class="text-sm font-bold text-primary mb-3 flex items-center gap-2"><i class="fas fa-keyboard"></i> คีย์ลัด (30+)</h4>
    <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
        <div class="flex justify-between"><span>ค้นหา</span><kbd>F</kbd></div>
        <div class="flex justify-between"><span>Command</span><kbd>⌘K</kbd></div>
        <div class="flex justify-between"><span>ภาพสไลด์</span><kbd>S</kbd></div>
        <div class="flex justify-between"><span>มีเดีย</span><kbd>P</kbd></div>
        <div class="flex justify-between"><span>3D</span><kbd>3</kbd></div>
        <div class="flex justify-between"><span>Mosaic</span><kbd>O</kbd></div>
        <div class="flex justify-between"><span>Ken Burns</span><kbd>K</kbd></div>
        <div class="flex justify-between"><span>Video Pipeline</span><kbd>V</kbd></div>
        <div class="flex justify-between"><span>เลือกหลาย</span><kbd>M</kbd></div>
        <div class="flex justify-between"><span>Zen Mode</span><kbd>Z</kbd></div>
        <div class="flex justify-between"><span>ซ่อน UI</span><kbd>H</kbd></div>
        <div class="flex justify-between"><span>ปิด</span><kbd>Esc</kbd></div>
        <div class="flex justify-between"><span>ลบ</span><kbd>Del</kbd></div>
        <div class="flex justify-between"><span>เล่น/หยุด</span><kbd>Space</kbd></div>
        <div class="flex justify-between"><span>วิดีโอ ±5s</span><kbd>← →</kbd></div>
        <div class="flex justify-between"><span>วิดีโอ ±10s</span><kbd>J L</kbd></div>
        <div class="flex justify-between"><span>frame</span><kbd>, .</kbd></div>
        <div class="flex justify-between"><span>volume</span><kbd>↑ ↓</kbd></div>
        <div class="flex justify-between"><span>เต็มจอ</span><kbd>F</kbd></div>
        <div class="flex justify-between"><span>Theater</span><kbd>T</kbd></div>
        <div class="flex justify-between"><span>ซูมดิจิทัล</span><kbd>Z</kbd></div>
        <div class="flex justify-between"><span>Slow-mo</span><kbd>⇧S</kbd></div>
        <div class="flex justify-between"><span>Mute</span><kbd>M</kbd></div>
    </div>
</div>
<button onclick="document.getElementById('shortcutsHelp').classList.toggle('hidden')" class="fixed bottom-5 left-5 z-[9998] w-11 h-11 rounded-full glass shadow-2xl flex items-center justify-center hover:border-primary hover:text-primary transition" title="คีย์ลัด"><i class="fas fa-keyboard"></i></button>

<form id="actionForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action_type" id="actType"><input type="hidden" name="item_name" id="actItem">
    <input type="hidden" name="new_name" id="actNewName"><input type="hidden" name="target_folder" id="actTargetFolder">
</form>
<form id="batchForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action_type" id="batchActType"><input type="hidden" name="target_folder" id="batchTargetFolder">
    <input type="hidden" name="selected_items" id="batchItems">
</form>
<form id="tagForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action_type" value="set_tag"><input type="hidden" name="item_name" id="tagItem"><input type="hidden" name="tag" id="tagValue">
</form>
<form id="ratingForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action_type" value="set_rating"><input type="hidden" name="item_name" id="ratingItem"><input type="hidden" name="rating" id="ratingValue">
</form>
<form id="commentForm" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action_type" value="set_comment"><input type="hidden" name="item_name" id="commentItem"><input type="hidden" name="comment" id="commentValue">
</form>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
const mediaFiles = <?php echo json_encode($mediaFiles); ?>;
const imagesOnly = <?php echo json_encode($imagesOnly); ?>;
const videosOnly = <?php echo json_encode($videosOnly); ?>;
const audioOnly  = <?php echo json_encode($audioOnly); ?>;
const folderImagesMap = <?php echo json_encode($folderImagesMap); ?>;
const folderVideosMap = <?php echo json_encode($folderVideosMap); ?>;
const folderMediaPathMap = <?php echo json_encode($folderMediaPathMap); ?>;
const allFiles = <?php echo json_encode(array_map(function($f) { return ['name'=>$f['name'],'path'=>$f['path'],'type'=>$f['type'],'relPath'=>$f['relPath'],'rating'=>$f['rating']]; }, $filesList)); ?>;
const currentFolderPath = <?php echo json_encode($currentFolder); ?>;
const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
const typeStats = <?php echo json_encode($typeStats); ?>;

let swiperInstance = null, currentItemToMove = '', currentMediaList = [];
let isAutoplay = false, playSpeed = 3000, loopMode = 'all', currentRatioMode = 'contain';
let isImageOnlyMode = false, isVideoMode = false, isAudioMode = false;
let isSelectMode = false, selectedItems = new Set();
let viewMode = localStorage.getItem('cloudViewMode') || 'grid';
let sortMode = localStorage.getItem('cloudSortMode') || 'name-asc';
let isCompactMode = localStorage.getItem('compactMode') === 'true';
let activeVideoEl = null, videoFilters = {hue:0,sat:100,bri:100}, videoTransforms = {flipX:false,flipY:false,rotate:0};
let abLoop = {a:null,b:null}, videoBookmarks = [], videoChapters = [];
let isZenMode = false, isVoiceOn = false, recognition = null;
let isShuffle = false, isTheaterMode = false, isMiniPlayer = false;
let audioCtx = null, analyser = null, audioSource = null, audioElement = null;
let eqFilter = null;

function showToast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toastContainer');
    const colors = {success:'bg-green-500',error:'bg-red-500',info:'bg-blue-500',warning:'bg-yellow-500 text-black'};
    const icons  = {success:'fa-check-circle',error:'fa-exclamation-circle',info:'fa-info-circle',warning:'fa-exclamation-triangle'};
    const toast = document.createElement('div');
    toast.className = `toast ${colors[type]||colors.info} text-white px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 min-w-[260px]`;
    toast.innerHTML = `<i class="fas ${icons[type]||icons.info}"></i><span class="flex-1 text-sm font-medium">${message}</span><button class="text-white/80 hover:text-white" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.transition='all .3s'; toast.style.opacity='0'; toast.style.transform='translateX(40px)'; setTimeout(()=>toast.remove(),300); }, duration);
}

function handleUpload(input) {
    if (input.files.length === 0) return;
    if (input.files.length > 20) { showToast('อัพโหลดได้สูงสุด 20 ไฟล์', 'error'); input.value=''; return; }
    const fd = new FormData(); fd.append('csrf_token', CSRF_TOKEN);
    for (let i = 0; i < input.files.length; i++) fd.append('images[]', input.files[i]);
    const pc = document.getElementById('uploadProgressContainer');
    const pb = document.getElementById('uploadProgressBar');
    const pt = document.getElementById('uploadProgressText');
    pc.classList.remove('hidden'); pb.style.width='0%'; pt.textContent='0%';
    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.href, true);
    xhr.upload.onprogress = (e) => { if (e.lengthComputable) { const p=Math.round(e.loaded/e.total*100); pb.style.width=p+'%'; pt.textContent=p+'% - กำลังอัพโหลด...'; } };
    xhr.onload = () => { if (xhr.status === 200) { pb.style.width='100%'; pt.textContent='เสร็จ!'; showToast('อัพโหลดเสร็จ','success'); setTimeout(()=>window.location.reload(),800); } else { showToast('ผิดพลาด HTTP '+xhr.status,'error'); pc.classList.add('hidden'); } };
    xhr.onerror = () => { showToast('ไม่สามารถเชื่อมต่อ','error'); pc.classList.add('hidden'); };
    xhr.send(fd);
}
['dragenter','dragover'].forEach(ev => document.body.addEventListener(ev, (e) => { e.preventDefault(); document.body.classList.add('drop-active'); }));
['dragleave','drop'].forEach(ev => document.body.addEventListener(ev, (e) => { e.preventDefault(); if (ev==='dragleave' && e.target !== document.body) return; document.body.classList.remove('drop-active'); }));
document.body.addEventListener('drop', (e) => { const fs = e.dataTransfer.files; if (fs.length === 0) return; const inp = document.getElementById('up'); const dt = new DataTransfer(); for (let i=0; i<fs.length; i++) dt.items.add(fs[i]); inp.files = dt.files; handleUpload(inp); });

function doDelete(item) { if (confirm('ลบ ['+item+'] ?')) { document.getElementById('actType').value='delete'; document.getElementById('actItem').value=item; document.getElementById('actionForm').submit(); } }
function doRename(item) { const n = prompt('ชื่อใหม่สำหรับ ['+item+']:', item); if (n && n.trim() !== '' && n !== item) { document.getElementById('actType').value='rename'; document.getElementById('actItem').value=item; document.getElementById('actNewName').value=n.trim(); document.getElementById('actionForm').submit(); } }
function doMove(item) { currentItemToMove = item; document.getElementById('moveModal').classList.remove('hidden'); document.getElementById('moveModal').classList.add('flex'); }
function confirmMove() { const t = document.getElementById('moveTargetSelect').value; if (currentItemToMove === '__batch__') { document.getElementById('batchActType').value='batch_move'; document.getElementById('batchTargetFolder').value=t; document.getElementById('batchItems').value=JSON.stringify(Array.from(selectedItems)); document.getElementById('batchForm').submit(); } else { document.getElementById('actType').value='move'; document.getElementById('actItem').value=currentItemToMove; document.getElementById('actTargetFolder').value=t; document.getElementById('actionForm').submit(); } }
function toggleFavorite(relPath, tag) { document.getElementById('tagItem').value=relPath; document.getElementById('tagValue').value=tag; document.getElementById('tagForm').submit(); }
function setRating(relPath, rating) { document.getElementById('ratingItem').value=relPath; document.getElementById('ratingValue').value=rating; document.getElementById('ratingForm').submit(); }
function setComment(relPath, comment) { document.getElementById('commentItem').value=relPath; document.getElementById('commentValue').value=comment; document.getElementById('commentForm').submit(); }
function openFolderMeta(folderName, isLocked) {
    document.getElementById('metaTargetFolder').value = folderName;
    document.getElementById('metaNewName').value = folderName;
    document.getElementById('metaIsLocked').checked = isLocked;
    togglePasswordField();
    const cg = document.getElementById('metaCoverGrid'); const hi = document.getElementById('metaCoverSelectHidden');
    cg.innerHTML = ''; hi.value = '';
    const nd = document.createElement('div');
    nd.className = 'h-20 rounded-lg flex items-center justify-center cursor-pointer border-2 border-primary transition text-center text-xs';
    nd.style.background = 'var(--item-bg)'; nd.innerHTML = '<span class="text-th-text/80"><i class="fas fa-sync-alt"></i><br>สลับอัตโนมัติ</span>';
    nd.onclick = function() { document.querySelectorAll('#metaCoverGrid img, #metaCoverGrid > div').forEach(el => el.classList.remove('border-primary')); this.classList.add('border-primary'); hi.value = ''; };
    cg.appendChild(nd);
    if (folderImagesMap[folderName] && folderImagesMap[folderName].length > 0) {
        folderImagesMap[folderName].forEach(img => {
            const ie = document.createElement('img');
            const bp = currentFolderPath !== '' ? currentFolderPath + '/' : '';
            ie.src = 'uploads/' + bp + folderName + '/' + img;
            ie.className = 'w-full h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-primary transition';
            ie.onclick = function() { document.querySelectorAll('#metaCoverGrid img, #metaCoverGrid > div').forEach(el => el.classList.remove('border-primary')); this.classList.add('border-primary'); hi.value = img; };
            cg.appendChild(ie);
        });
    }
    document.getElementById('folderMetaModal').classList.remove('hidden');
    document.getElementById('folderMetaModal').classList.add('flex');
}
function togglePasswordField() { document.getElementById('passwordFields').classList.toggle('hidden', !document.getElementById('metaIsLocked').checked); }
function closeModal(id) { const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }
document.querySelectorAll('#moveModal, #folderMetaModal, #fileInfoModal').forEach(m => m.addEventListener('click', (e) => { if (e.target === m) closeModal(m.id); }));

function showFileInfo(file) {
    const body = document.getElementById('fileInfoBody');
    body.innerHTML = `
        <div class="flex items-center gap-3 pb-3 border-b border-input-border">
            <i class="fas ${fileTypeIcon(file.type)} text-3xl text-primary"></i>
            <div class="flex-1 min-w-0"><div class="font-semibold truncate">${file.name}</div><div class="text-xs text-th-text/60">${file.ext.toUpperCase()} • ${file.type}</div></div>
        </div>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div><span class="text-th-text/50">ขนาด:</span> <span class="font-semibold">${file.sizeText}</span></div>
            <div><span class="text-th-text/50">ประเภท:</span> <span class="font-semibold">${file.type}</span></div>
            <div class="col-span-2"><span class="text-th-text/50">วันที่:</span> <span class="font-semibold">${file.dateText}</span></div>
            ${file.tag ? `<div class="col-span-2"><span class="text-th-text/50">แท็ก:</span> <span class="font-semibold text-yellow-400"><i class="fas fa-tag"></i> ${file.tag}</span></div>` : ''}
        </div>
        <div class="pt-3 border-t border-input-border">
            <label class="block text-xs font-bold text-primary mb-2">คะแนน (1-5 ดาว)</label>
            <div class="flex gap-1" id="ratingStars">
                ${[1,2,3,4,5].map(n => `<i class="fas fa-star text-2xl cursor-pointer ${n <= file.rating ? 'text-yellow-400' : 'text-th-text/30'}" onclick="setRating('${file.relPath}', ${n === file.rating ? 0 : n})"></i>`).join('')}
            </div>
        </div>
        <div class="pt-3 border-t border-input-border">
            <label class="block text-xs font-bold text-primary mb-2">ความคิดเห็น</label>
            <textarea id="commentText" class="w-full px-3 py-2 rounded-lg bg-input-bg border border-input-border text-th-text outline-none focus:border-primary text-sm" rows="2" placeholder="เพิ่มความคิดเห็น...">${file.comment ? file.comment.text : ''}</textarea>
            <button onclick="setComment('${file.relPath}', document.getElementById('commentText').value)" class="mt-2 px-3 py-1 rounded-full bg-primary text-btn-text text-xs font-semibold">บันทึกความเห็น</button>
        </div>
        <div class="flex gap-2 pt-3 border-t border-input-border">
            <button onclick="closeModal('fileInfoModal'); openSinglePreview('${file.path}','${file.type}','${file.name}')" class="flex-1 px-3 py-2 rounded-full bg-primary text-btn-text text-sm font-semibold hover:opacity-90 transition"><i class="fas fa-eye mr-1"></i> เปิด</button>
            <a href="${file.path}" download class="flex-1 px-3 py-2 rounded-full border border-input-border text-th-text text-sm font-semibold hover:border-primary transition text-center"><i class="fas fa-download mr-1"></i> ดาวน์โหลด</a>
            <button onclick="copyPath('${file.path}')" class="px-3 py-2 rounded-full border border-input-border text-th-text text-sm font-semibold hover:border-primary transition" title="คัดลอก path"><i class="fas fa-copy"></i></button>
        </div>
    `;
    document.getElementById('fileInfoModal').classList.remove('hidden');
    document.getElementById('fileInfoModal').classList.add('flex');
}
function copyPath(path) { navigator.clipboard.writeText(window.location.origin + '/' + path).then(() => showToast('คัดลอก path แล้ว', 'success')); }
function fileTypeIcon(type) { const m = {image:'fa-file-image',video:'fa-file-video',audio:'fa-file-audio',text:'fa-file-code',pdf:'fa-file-pdf',archive:'fa-file-archive',doc:'fa-file-word',other:'fa-file'}; return m[type] || 'fa-file'; }

function showContextMenu(e, file) {
    e.preventDefault();
    const menu = document.getElementById('contextMenu');
    menu.innerHTML = `
        <div class="context-menu-item" onclick="handleFileClick('${file.path}','${file.type}','${file.name}'); hideContextMenu()"><i class="fas fa-eye"></i> เปิด</div>
        <div class="context-menu-item" onclick="showFileInfo(${JSON.stringify(file).replace(/"/g,'&quot;')}); hideContextMenu()"><i class="fas fa-info-circle"></i> ข้อมูล</div>
        <div class="context-menu-item" onclick="copyPath('${file.path}'); hideContextMenu()"><i class="fas fa-copy"></i> คัดลอก path</div>
        <div class="context-menu-item" onclick="toggleFavorite('${file.relPath}', '${file.tag === 'favorite' ? '' : 'favorite'}'); hideContextMenu()"><i class="fas fa-star"></i> ${file.tag === 'favorite' ? 'ยกเลิกโปรด' : 'เพิ่มโปรด'}</div>
        <div class="context-menu-item" onclick="doRename('${file.name}'); hideContextMenu()"><i class="fas fa-edit"></i> เปลี่ยนชื่อ</div>
        <div class="context-menu-item" onclick="doMove('${file.name}'); hideContextMenu()"><i class="fas fa-arrows-alt"></i> ย้าย</div>
        <div class="context-menu-item" onclick="doDelete('${file.name}'); hideContextMenu()" style="color:var(--danger)"><i class="fas fa-trash"></i> ลบ</div>
    `;
    menu.style.left = e.clientX + 'px';
    menu.style.top = e.clientY + 'px';
    menu.classList.remove('hidden');
}
function hideContextMenu() { document.getElementById('contextMenu').classList.add('hidden'); }
document.addEventListener('click', hideContextMenu);
document.addEventListener('scroll', hideContextMenu, true);

document.getElementById('searchInput')?.addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('#gridContainer .item-card').forEach(card => {
        const name = (card.dataset.name || '').toLowerCase();
        card.classList.toggle('search-hidden', !(!q || name.includes(q)));
    });
});

function applySort() {
    const sel = document.getElementById('sortSelect');
    if (!sel) return;
    sortMode = sel.value; localStorage.setItem('cloudSortMode', sortMode);
    const grid = document.getElementById('gridContainer'); if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.item-card'));
    const [field, dir] = sortMode.split('-');
    cards.sort((a, b) => {
        let va, vb;
        if (field === 'name') { va = a.dataset.name?.toLowerCase() || ''; vb = b.dataset.name?.toLowerCase() || ''; }
        else if (field === 'size') { va = parseInt(a.dataset.size)||0; vb = parseInt(b.dataset.size)||0; }
        else if (field === 'date') { va = parseInt(a.dataset.date)||0; vb = parseInt(a.dataset.date)||0; }
        else if (field === 'type') { va = a.dataset.type||''; vb = b.dataset.type||''; }
        else if (field === 'rating') { va = parseInt(a.dataset.rating)||0; vb = parseInt(b.dataset.rating)||0; }
        const aF = a.dataset.type === 'folder', bF = b.dataset.type === 'folder';
        if (aF && !bF) return -1; if (!aF && bF) return 1;
        if (va < vb) return dir === 'asc' ? -1 : 1; if (va > vb) return dir === 'asc' ? 1 : -1; return 0;
    });
    cards.forEach(c => grid.appendChild(c));
}

function setView(mode) {
    viewMode = mode; localStorage.setItem('cloudViewMode', mode);
    const grid = document.getElementById('gridContainer'); if (!grid) return;
    grid.parentElement.classList.toggle('list-view', mode === 'list');
    grid.parentElement.classList.toggle('detail-view', mode === 'detail');
    grid.parentElement.classList.toggle('filmstrip-view', mode === 'filmstrip');
    grid.parentElement.classList.toggle('masonry-grid', mode === 'masonry');
    if (mode === 'filmstrip') { grid.style.display='flex'; grid.style.flexWrap='nowrap'; grid.style.overflowX='auto'; grid.querySelectorAll('.item-card').forEach(c => c.style.flex = '0 0 200px'); }
    else if (mode === 'masonry') { grid.style.display='block'; grid.querySelectorAll('.item-card').forEach(c => c.style.flex = ''); }
    else { grid.style.display='grid'; grid.style.flexWrap=''; grid.style.overflowX=''; grid.querySelectorAll('.item-card').forEach(c => c.style.flex = ''); }
    const sel = document.getElementById('viewModeSelect'); if (sel) sel.value = mode;
}
function toggleCompactMode() {
    isCompactMode = !isCompactMode;
    localStorage.setItem('compactMode', isCompactMode);
    document.body.classList.toggle('compact-mode', isCompactMode);
    document.getElementById('compactBtn').classList.toggle('bg-primary', isCompactMode);
    document.getElementById('compactBtn').classList.toggle('text-btn-text', isCompactMode);
    showToast(isCompactMode ? 'Compact mode: การ์ดเล็กลง' : 'Compact mode: ออก', 'info');
}
document.addEventListener('DOMContentLoaded', () => {
    const sortSel = document.getElementById('sortSelect');
    if (sortSel) { sortSel.value = sortMode; applySort(); }
    setView(viewMode);
    if (isCompactMode) { document.body.classList.add('compact-mode'); document.getElementById('compactBtn').classList.add('bg-primary','text-btn-text'); }
});

function toggleSelectMode() {
    isSelectMode = !isSelectMode; selectedItems.clear();
    document.getElementById('selectModeBtn').classList.toggle('bg-primary', isSelectMode);
    document.getElementById('selectModeBtn').classList.toggle('text-btn-text', isSelectMode);
    document.getElementById('batchActions').classList.toggle('hidden', !isSelectMode);
    document.getElementById('batchActions').classList.toggle('flex', isSelectMode);
    document.querySelectorAll('.select-btn').forEach(btn => { btn.classList.toggle('hidden', !isSelectMode); btn.classList.toggle('flex', isSelectMode); });
    document.querySelectorAll('.item-card').forEach(c => c.classList.remove('selected'));
    updateSelectedCount();
}
function exitSelectMode() { if (selectedItems.size > 0 && !confirm('ออกจากโหมดเลือก?')) return; toggleSelectMode(); }
function toggleSelectItem(btn, name) {
    const card = btn.closest('.item-card'); const check = btn.querySelector('i');
    if (selectedItems.has(name)) { selectedItems.delete(name); card.classList.remove('selected'); check.classList.add('opacity-0'); }
    else { selectedItems.add(name); card.classList.add('selected'); check.classList.remove('opacity-0'); }
    updateSelectedCount();
}
function updateSelectedCount() { const el = document.getElementById('selectedCount'); if (el) el.textContent = selectedItems.size + ' ที่เลือก'; }
function batchDelete() { if (selectedItems.size === 0) { showToast('ยังไม่ได้เลือก','warning'); return; } if (!confirm(`ลบ ${selectedItems.size} รายการ?`)) return; document.getElementById('batchActType').value='batch_delete'; document.getElementById('batchItems').value=JSON.stringify(Array.from(selectedItems)); document.getElementById('batchForm').submit(); }
function batchMove() { if (selectedItems.size === 0) { showToast('ยังไม่ได้เลือก','warning'); return; } currentItemToMove='__batch__'; document.getElementById('moveModal').classList.remove('hidden'); document.getElementById('moveModal').classList.add('flex'); }
function batchTag() {
    if (selectedItems.size === 0) { showToast('ยังไม่ได้เลือก','warning'); return; }
    const tag = prompt('ติดแท็กอะไร?', 'favorite'); if (!tag) return;
    const items = Array.from(selectedItems); let i = 0;
    const next = () => { if (i >= items.length) { window.location.reload(); return; } const f = document.getElementById('tagForm'); document.getElementById('tagItem').value = items[i]; document.getElementById('tagValue').value = tag; const fd = new FormData(f); fetch(window.location.href, {method:'POST',body:fd}).then(() => { i++; next(); }); };
    next();
}

function handleFileClick(path, type, name) {
    if (isSelectMode) return;
    if (type === 'other' || type === 'archive' || type === 'doc') { const a = document.createElement('a'); a.href=path; a.download=name; document.body.appendChild(a); a.click(); document.body.removeChild(a); return; }
    openSinglePreview(path, type, name);
}

function getRatioStyle() { return `width:100%;height:100%;object-fit:${currentRatioMode};transition:.3s;`; }
function createMediaSlide(file) {
    const style = getRatioStyle();
    let content = '';
    if (file.type === 'image') content = `<div class="swiper-zoom-container"><img src="${file.path}" class="media-preview-item" style="${style}" onload="extractColors(this)"></div>`;
    else if (file.type === 'video') content = `<video controls autoplay class="media-preview-item video-filter" style="${style} outline:none;" onloadedmetadata="onVideoLoaded(this)" ontimeupdate="onVideoTimeUpdate(this)"><source src="${file.path}"></video>`;
    else if (file.type === 'audio') content = `<div class="audio-wrapper"><i class="fas fa-music text-5xl text-primary mb-4"></i><br><audio controls autoplay id="audioPlayerEl" onloadedmetadata="setupAudioVisualizer(this)" ontimeupdate="updateAudioTime(this)"><source src="${file.path}"></audio><div class="mt-4 text-xs text-th-text/60" id="audioTimeDisplay">0:00 / 0:00</div></div>`;
    else if (file.type === 'text' || file.type === 'pdf') content = `<iframe src="${file.path}" class="iframe-wrapper"></iframe>`;
    return `<div class="swiper-slide">${content}</div>`;
}
function initSwiper(isSlideshow, imageOnly = false, videoMode = false, audioMode = false) {
    if (swiperInstance) swiperInstance.destroy(true, true);
    isImageOnlyMode = imageOnly; isVideoMode = videoMode; isAudioMode = audioMode;
    const modal = document.getElementById('mainModal');
    modal.classList.remove('ui-hidden');
    document.querySelector('.toggle-ui-standalone i').className = 'fas fa-eye-slash';
    document.getElementById('slideshowControls').classList.toggle('hidden', !isSlideshow);
    document.getElementById('slideshowControls').classList.toggle('flex', isSlideshow);
    document.getElementById('speedControlGroup').classList.toggle('hidden', !imageOnly);
    document.getElementById('videoControlsGroup').classList.toggle('hidden', !videoMode);
    document.getElementById('audioVisualizer').classList.toggle('hidden', !audioMode);
    ['btnScreenshot','btnFullscreen','btnPip','btnTheater','btnMini'].forEach(id => {
        document.getElementById(id).classList.toggle('hidden', !videoMode);
    });
    currentRatioMode = 'contain';
    document.querySelectorAll('.ratio-btn').forEach(b => {
        const isFit = b.innerText.includes('Fit');
        b.classList.toggle('active', isFit);
        if (isFit) { b.classList.add('bg-primary','text-btn-text','border-primary'); b.classList.remove('bg-input-bg','text-th-text'); }
        else { b.classList.remove('bg-primary','text-btn-text','border-primary'); b.classList.add('bg-input-bg','text-th-text'); }
    });
    swiperInstance = new Swiper('.swiper', {
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        keyboard: { enabled: true }, zoom: true,
        loop: isSlideshow && currentMediaList.length > 1,
        autoplay: isSlideshow ? { delay: playSpeed, disableOnInteraction: false } : false,
        on: { slideChange: () => { updateTopButtons(); resetVideoFilters(); abLoop = {a:null,b:null}; } }
    });
    if (isSlideshow) {
        swiperInstance.autoplay.stop(); isAutoplay = false;
        document.getElementById('autoPlayBtn').innerHTML = '<i class="fas fa-play mr-1"></i> เล่นสไลด์';
        if (imageOnly) { setTimeout(() => { swiperInstance.autoplay.start(); isAutoplay = true; document.getElementById('autoPlayBtn').innerHTML = '<i class="fas fa-pause mr-1"></i> หยุดเล่น'; }, 300); }
    }
    loopMode = 'all';
    if (document.getElementById('loopModeBtn')) document.getElementById('loopModeBtn').innerHTML = '<i class="fas fa-sync mr-1"></i> วนทั้งหมด';
    updateTopButtons();
}
function updateTopButtons() {
    if (!swiperInstance || currentMediaList.length === 0) return;
    const am = currentMediaList[swiperInstance.realIndex]; if (!am) return;
    document.getElementById('btnOpenFull').onclick = () => window.open(am.path, '_blank');
    document.getElementById('btnDownload').onclick = () => { const a=document.createElement('a'); a.href=am.path; a.download=am.name; document.body.appendChild(a); a.click(); document.body.removeChild(a); };
    if (loopMode === 'one') swiperInstance.autoplay.stop();
    setTimeout(() => { activeVideoEl = document.querySelector('.swiper-slide-active video'); if (activeVideoEl) { activeVideoEl.playbackRate = 1; applyVideoFilters(); } audioElement = document.querySelector('.swiper-slide-active audio'); if (audioElement) setupAudioVisualizer(audioElement); }, 100);
}
function openSinglePreview(path, type, name) { currentMediaList = [{path,type,name}]; document.getElementById('swiperWrapper').innerHTML = createMediaSlide(currentMediaList[0]); document.getElementById('mainModal').classList.remove('hidden'); initSwiper(false, false, type === 'video', type === 'audio'); }
function openSlideshow() { if (mediaFiles.length === 0) { showToast('ไม่มีไฟล์มีเดีย','warning'); return; } currentMediaList = mediaFiles; document.getElementById('swiperWrapper').innerHTML = currentMediaList.map(f => createMediaSlide(f)).join(''); document.getElementById('mainModal').classList.remove('hidden'); initSwiper(true, false, false, false); }
function openImageSlideshow() { if (imagesOnly.length === 0) { showToast('ไม่มีภาพ','warning'); return; } currentMediaList = imagesOnly; document.getElementById('swiperWrapper').innerHTML = currentMediaList.map(f => createMediaSlide(f)).join(''); document.getElementById('mainModal').classList.remove('hidden'); initSwiper(true, true, false, false); showToast(`เริ่มเล่นภาพสไลด์ ${imagesOnly.length} รูป`, 'info'); }
function openVideoPipeline() { if (videosOnly.length === 0) { showToast('ไม่มีวิดีโอ','warning'); return; } currentMediaList = videosOnly; document.getElementById('swiperWrapper').innerHTML = currentMediaList.map(f => createMediaSlide(f)).join(''); document.getElementById('mainModal').classList.remove('hidden'); initSwiper(true, false, true, false); showToast(`Video Pipeline: ${videosOnly.length} วิดีโอ`, 'info'); }
function openAudioPlayer() { if (audioOnly.length === 0) { showToast('ไม่มีไฟล์เสียง','warning'); return; } currentMediaList = audioOnly; document.getElementById('swiperWrapper').innerHTML = currentMediaList.map(f => createMediaSlide(f)).join(''); document.getElementById('mainModal').classList.remove('hidden'); initSwiper(true, false, false, true); showToast(`Audio Player: ${audioOnly.length} เพลง`, 'info'); }
function closePlayer() { const m = document.getElementById('mainModal'); m.classList.add('hidden'); m.classList.remove('ui-hidden'); document.querySelector('.toggle-ui-standalone i').className = 'fas fa-eye-slash'; document.getElementById('swiperWrapper').innerHTML = ''; if (swiperInstance) swiperInstance.destroy(true, true); document.querySelectorAll('video, audio').forEach(m => m.pause()); closePip(); if (audioCtx) { audioCtx.close(); audioCtx = null; } }
function toggleUI() { const m = document.getElementById('mainModal'); const i = document.querySelector('.toggle-ui-standalone i'); m.classList.toggle('ui-hidden'); i.className = m.classList.contains('ui-hidden') ? 'fas fa-eye' : 'fas fa-eye-slash'; }
document.getElementById('swiperWrapper').addEventListener('dblclick', () => { const m = document.getElementById('mainModal'); if (m.classList.contains('ui-hidden')) toggleUI(); });
function setRatio(mode, btn) { currentRatioMode = mode; document.querySelectorAll('.media-preview-item').forEach(el => { el.style.width='100%'; el.style.height='100%'; el.style.objectFit=mode; }); if (btn) { document.querySelectorAll('.ratio-btn').forEach(b => { b.classList.remove('active','bg-primary','text-btn-text','border-primary'); b.classList.add('bg-input-bg','text-th-text'); }); btn.classList.add('active','bg-primary','text-btn-text','border-primary'); btn.classList.remove('bg-input-bg','text-th-text'); } }
function toggleAutoplay() { if (!swiperInstance) return; const b = document.getElementById('autoPlayBtn'); if (isAutoplay) { swiperInstance.autoplay.stop(); b.innerHTML='<i class="fas fa-play mr-1"></i> เล่นสไลด์'; isAutoplay=false; } else { if (loopMode==='one') toggleLoopMode(); swiperInstance.autoplay.start(); b.innerHTML='<i class="fas fa-pause mr-1"></i> หยุดเล่น'; isAutoplay=true; } }
function toggleShuffle() { isShuffle = !isShuffle; document.getElementById('shuffleBtn').classList.toggle('bg-primary', isShuffle); document.getElementById('shuffleBtn').classList.toggle('text-btn-text', isShuffle); showToast(isShuffle ? 'เปิดสุ่ม' : 'ปิดสุ่ม', 'info'); }
function updateSpeedFromSlider(ms) { playSpeed = parseInt(ms); document.getElementById('speedValueLabel').textContent = (playSpeed/1000).toFixed(1) + ' วิ/ภาพ'; if (swiperInstance) { swiperInstance.params.autoplay.delay = playSpeed; if (isAutoplay) { swiperInstance.autoplay.stop(); swiperInstance.autoplay.start(); } } }
function toggleLoopMode() { if (!swiperInstance) return; const b = document.getElementById('loopModeBtn'); if (loopMode === 'all') { loopMode = 'one'; b.innerHTML = '<i class="fas fa-redo mr-1"></i> วนภาพนี้'; if (isAutoplay) { swiperInstance.autoplay.stop(); document.getElementById('autoPlayBtn').innerHTML='<i class="fas fa-play mr-1"></i> เล่นสไลด์'; isAutoplay=false; } const v = document.querySelector('.swiper-slide-active video'); if (v) { v.loop = true; v.play(); } } else { loopMode = 'all'; b.innerHTML = '<i class="fas fa-sync mr-1"></i> วนทั้งหมด'; const v = document.querySelector('.swiper-slide-active video'); if (v) v.loop = false; } }

function onVideoLoaded(v) { activeVideoEl = v; document.getElementById('videoTimeDuration').textContent = formatTime(v.duration); const st = document.getElementById('scrubThumb'); if (st && v.currentSrc) st.src = v.currentSrc; }
function onVideoTimeUpdate(v) { if (!v.duration) return; const p = (v.currentTime / v.duration) * 100; document.getElementById('videoProgress').style.width = p + '%'; document.getElementById('videoTimeCurrent').textContent = formatTime(v.currentTime); if (abLoop.a !== null && abLoop.b !== null && v.currentTime >= abLoop.b) v.currentTime = abLoop.a; }
function formatTime(s) { if (isNaN(s)) return '0:00'; const m = Math.floor(s/60); const sec = Math.floor(s%60); return `${m}:${sec.toString().padStart(2,'0')}`; }
function seekVideo(e) { if (!activeVideoEl) return; const r = e.currentTarget.getBoundingClientRect(); const p = (e.clientX - r.left) / r.width; activeVideoEl.currentTime = p * activeVideoEl.duration; }
function videoSeek(s) { if (activeVideoEl) activeVideoEl.currentTime = Math.max(0, Math.min(activeVideoEl.duration, activeVideoEl.currentTime + s)); }
function videoFrameStep(d) { if (!activeVideoEl) return; activeVideoEl.pause(); activeVideoEl.currentTime += d / 30; }
function setVideoSpeed(s) { if (activeVideoEl) activeVideoEl.playbackRate = s; }
function setVideoFilter(t, v) { videoFilters[t] = parseInt(v); document.getElementById(t+'Val').textContent = v; applyVideoFilters(); }
function applyVideoFilters() { if (!activeVideoEl) return; activeVideoEl.style.filter = `hue-rotate(${videoFilters.hue}deg) saturate(${videoFilters.sat}%) brightness(${videoFilters.bri}%)`; let tr = ''; if (videoTransforms.flipX) tr += 'scaleX(-1) '; if (videoTransforms.flipY) tr += 'scaleY(-1) '; if (videoTransforms.rotate) tr += `rotate(${videoTransforms.rotate}deg) `; activeVideoEl.style.transform = tr.trim(); }
function videoTransform(t) { if (t === 'flipX') videoTransforms.flipX = !videoTransforms.flipX; else if (t === 'flipY') videoTransforms.flipY = !videoTransforms.flipY; else if (t === 'rotate') videoTransforms.rotate = (videoTransforms.rotate + 90) % 360; applyVideoFilters(); }
function digitalZoom() { if (!activeVideoEl) return; const cur = parseFloat(activeVideoEl.dataset.zoom || '1'); const nz = cur >= 3 ? 1 : cur + 0.5; activeVideoEl.dataset.zoom = nz; let tr = activeVideoEl.style.transform.replace(/scale\([^)]*\)/g, '').trim(); activeVideoEl.style.transform = `${tr} scale(${nz})`.trim(); showToast(`ซูม ${nz}x`, 'info'); }
function resetVideoFilters() { videoFilters = {hue:0,sat:100,bri:100}; videoTransforms = {flipX:false,flipY:false,rotate:0}; if (activeVideoEl) activeVideoEl.dataset.zoom = '1'; document.querySelectorAll('#videoControlsGroup input[type=range]').forEach(r => r.value = r.id === 'hue' ? 0 : 100); document.getElementById('hueVal').textContent='0'; document.getElementById('satVal').textContent='100'; document.getElementById('briVal').textContent='100'; applyVideoFilters(); }
function toggleABLoop() { if (!activeVideoEl) return; if (abLoop.a === null) { abLoop.a = activeVideoEl.currentTime; showToast(`ตั้ง A ที่ ${formatTime(abLoop.a)}`,'info'); } else if (abLoop.b === null) { abLoop.b = activeVideoEl.currentTime; showToast(`ตั้ง B ที่ ${formatTime(abLoop.b)} - วนซ้ำ`,'success'); } else { abLoop = {a:null,b:null}; showToast('ยกเลิก A-B','info'); } }
function bookmarkFrame() { if (!activeVideoEl) return; const t = activeVideoEl.currentTime; const n = currentMediaList[swiperInstance.realIndex]?.name || 'video'; videoBookmarks.push({name:n, time:t}); showToast(`บันทึก bookmark ที่ ${formatTime(t)}`,'success'); }
function addChapter() { if (!activeVideoEl) return; const t = activeVideoEl.currentTime; videoChapters.push({time: t, label: `Ch ${videoChapters.length + 1}`}); const list = document.getElementById('chaptersList'); list.classList.remove('hidden'); list.innerHTML = videoChapters.map((c,i) => `<button class="px-2 py-1 rounded bg-input-bg border border-input-border text-xs hover:border-primary" onclick="activeVideoEl.currentTime=${c.time}">${c.label} (${formatTime(c.time)})</button>`).join(''); showToast(`เพิ่ม chapter ที่ ${formatTime(t)}`,'success'); }
function screenshotFrame() { if (!activeVideoEl) { showToast('ไม่มีวิดีโอ','warning'); return; } const c = document.createElement('canvas'); c.width = activeVideoEl.videoWidth; c.height = activeVideoEl.videoHeight; const ctx = c.getContext('2d'); ctx.filter = activeVideoEl.style.filter || 'none'; ctx.translate(c.width/2, c.height/2); if (videoTransforms.flipX) ctx.scale(-1,1); if (videoTransforms.flipY) ctx.scale(1,-1); if (videoTransforms.rotate) ctx.rotate(videoTransforms.rotate * Math.PI / 180); const z = parseFloat(activeVideoEl.dataset.zoom || '1'); if (z !== 1) ctx.scale(z, z); ctx.drawImage(activeVideoEl, -c.width/2, -c.height/2); c.toBlob(b => { const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = `screenshot_${Date.now()}.png`; a.click(); showToast('บันทึกภาพแล้ว','success'); }); }
function reverseVideo() { if (!activeVideoEl) return; const v = activeVideoEl; const dur = v.duration; const cur = v.currentTime; v.pause(); const step = 1/30; let pos = cur; v.currentTime = pos; const interval = setInterval(() => { pos -= step; if (pos <= 0) { clearInterval(interval); v.currentTime = 0; return; } v.currentTime = pos; }, 1000/30); showToast('เล่นกลับ - กด play เพื่อหยุด','info'); }
function slowMoPreset() { if (!activeVideoEl) return; activeVideoEl.playbackRate = 0.25; showToast('Slow-mo 0.25x','info'); }
function videoAudioOnly() { if (!activeVideoEl) return; activeVideoEl.style.opacity = '0.05'; showToast('โหมดเสียงอย่างเดียว - กดปิดเพื่อคืนค่า','info'); }
function toggleFullscreen() { if (!activeVideoEl) return; if (document.fullscreenElement) document.exitFullscreen(); else activeVideoEl.requestFullscreen(); }
function toggleTheater() { isTheaterMode = !isTheaterMode; document.getElementById('mainModal').style.background = isTheaterMode ? '#000' : '#000'; document.querySelectorAll('.top-panel, .bottom-panel').forEach(p => p.style.opacity = isTheaterMode ? '0.3' : '1'); showToast(isTheaterMode ? 'Theater Mode' : 'ออกจาก Theater','info'); }
function toggleMini() { isMiniPlayer = !isMiniPlayer; if (isMiniPlayer) { document.getElementById('mainModal').style.width = '640px'; document.getElementById('mainModal').style.height = '360px'; document.getElementById('mainModal').style.right = '20px'; document.getElementById('mainModal').style.left = 'auto'; document.getElementById('mainModal').style.top = 'auto'; document.getElementById('mainModal').style.bottom = '20px'; } else { document.getElementById('mainModal').style.width = ''; document.getElementById('mainModal').style.height = ''; document.getElementById('mainModal').style.right = ''; document.getElementById('mainModal').style.left = ''; document.getElementById('mainModal').style.top = ''; document.getElementById('mainModal').style.bottom = ''; } }

function togglePip() { const o = document.getElementById('pipOverlay'); const v = document.getElementById('pipVideo'); if (!o.classList.contains('hidden')) { closePip(); return; } if (!activeVideoEl) { showToast('เลือกวิดีโอก่อน','warning'); return; } v.src = activeVideoEl.currentSrc; v.currentTime = activeVideoEl.currentTime; v.play(); o.classList.remove('hidden'); showToast('PiP เปิด - ลาก/ปรับขนาดได้','info'); makeDraggable(o); }
function closePip() { const o = document.getElementById('pipOverlay'); document.getElementById('pipVideo').pause(); document.getElementById('pipVideo').src = ''; o.classList.add('hidden'); }
function makeDraggable(el) { let isDown=false, ox=0, oy=0; el.addEventListener('mousedown', (e) => { if (e.target.tagName === 'VIDEO' || e.target.tagName === 'BUTTON' || e.target.closest('button')) return; isDown = true; ox = e.clientX - el.offsetLeft; oy = e.clientY - el.offsetTop; el.style.cursor = 'grabbing'; }); document.addEventListener('mousemove', (e) => { if (!isDown) return; el.style.left = (e.clientX - ox) + 'px'; el.style.top = (e.clientY - oy) + 'px'; el.style.right = 'auto'; el.style.bottom = 'auto'; }); document.addEventListener('mouseup', () => { isDown = false; el.style.cursor = 'move'; }); }

function setupAudioVisualizer(audio) {
    audioElement = audio;
    try {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            analyser.fftSize = 128;
            eqFilter = audioCtx.createBiquadFilter();
            eqFilter.type = 'allpass';
            audioSource = audioCtx.createMediaElementSource(audio);
            audioSource.connect(eqFilter);
            eqFilter.connect(analyser);
            analyser.connect(audioCtx.destination);
        }
        drawVisualizer();
    } catch(e) { console.warn('Audio visualizer error:', e); }
}
function drawVisualizer() {
    if (!analyser) return;
    const canvas = document.getElementById('vizCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const bufferLength = analyser.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);
    const draw = () => {
        if (!analyser) return;
        requestAnimationFrame(draw);
        analyser.getByteFrequencyData(dataArray);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const barWidth = canvas.width / bufferLength;
        const primary = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#00f0ff';
        for (let i = 0; i < bufferLength; i++) {
            const barHeight = (dataArray[i] / 255) * canvas.height;
            ctx.fillStyle = primary;
            ctx.fillRect(i * barWidth, canvas.height - barHeight, barWidth - 1, barHeight);
        }
    };
    draw();
}
function updateAudioTime(audio) { const d = document.getElementById('audioTimeDisplay'); if (d) d.textContent = `${formatTime(audio.currentTime)} / ${formatTime(audio.duration)}`; }
function setEqPreset(preset) { if (!eqFilter || !audioCtx) return; eqFilter.type = preset === 'bass' ? 'lowshelf' : preset === 'treble' ? 'highshelf' : preset === 'vocal' ? 'peaking' : 'allpass'; if (preset === 'bass') eqFilter.frequency.value = 200, eqFilter.gain.value = 10; else if (preset === 'treble') eqFilter.frequency.value = 3000, eqFilter.gain.value = 10; else if (preset === 'vocal') eqFilter.frequency.value = 1500, eqFilter.gain.value = 5; else eqFilter.gain.value = 0; showToast(`EQ: ${preset}`, 'info'); }

function extractColors(img) {
    try {
        const c = document.createElement('canvas');
        const ctx = c.getContext('2d');
        c.width = 50; c.height = 50;
        ctx.drawImage(img, 0, 0, 50, 50);
        const data = ctx.getImageData(0, 0, 50, 50).data;
        const colors = {};
        for (let i = 0; i < data.length; i += 16) {
            const r = data[i] & 0xF0, g = data[i+1] & 0xF0, b = data[i+2] & 0xF0;
            const key = `${r},${g},${b}`;
            colors[key] = (colors[key] || 0) + 1;
        }
        const sorted = Object.entries(colors).sort((a,b) => b[1] - a[1]).slice(0, 5);
        if (sorted.length > 0) {
            const dom = sorted[0][0].split(',');
            const container = img.closest('.swiper-slide');
            if (container) {
                container.style.background = `linear-gradient(135deg, rgb(${dom[0]},${dom[1]},${dom[2]}) 0%, #000 100%)`;
            }
        }
    } catch(e) {}
}

const folderPreviewState = {};
function rotateFolderPreviews() {
    Object.keys(folderImagesMap).forEach(folderName => {
        const imgs = folderImagesMap[folderName];
        if (!imgs || imgs.length < 2) return;
        const els = document.querySelectorAll(`.folder-preview-img[data-folder="${CSS.escape(folderName)}"]`);
        if (els.length < 2) return;
        if (!(folderName in folderPreviewState)) folderPreviewState[folderName] = 0;
        folderPreviewState[folderName] = (folderPreviewState[folderName] + 1) % els.length;
        els.forEach((el, i) => el.classList.toggle('active', i === folderPreviewState[folderName]));
    });
}
setInterval(rotateFolderPreviews, 4000);

function openCylinderGallery() { if (imagesOnly.length === 0) { showToast('ไม่มีภาพ','warning'); return; } const inner = document.getElementById('cylinderInner'); inner.innerHTML = ''; const imgs = imagesOnly.slice(0, 16); const step = 360 / imgs.length; const radius = Math.max(200, imgs.length * 30); imgs.forEach((img, i) => { const d = document.createElement('div'); d.style.cssText = `position:absolute; width:200px; height:280px; transform: rotateY(${i*step}deg) translateZ(${radius}px); border:2px solid var(--primary); border-radius:12px; overflow:hidden; box-shadow:0 0 20px var(--primary);`; d.innerHTML = `<img src="${img.path}" style="width:100%;height:100%;object-fit:cover;cursor:pointer;" onclick="openSinglePreview('${img.path}','image','${img.name}')">`; inner.appendChild(d); }); document.getElementById('cylinderModal').classList.remove('hidden'); }
function openKenBurns() { if (imagesOnly.length === 0) { showToast('ไม่มีภาพ','warning'); return; } currentMediaList = imagesOnly; const effects = ['kenburns-zoom-in','kenburns-zoom-out','kenburns-pan-left','kenburns-pan-right','kenburns-pan-up','kenburns-pan-down']; document.getElementById('swiperWrapper').innerHTML = currentMediaList.map((f, i) => { const e = effects[i % effects.length]; return `<div class="swiper-slide"><div class="swiper-zoom-container ${e}" style="width:100%;height:100%;animation:${e} 8s ease-in-out infinite alternate;"><img src="${f.path}" style="width:100%;height:100%;object-fit:cover;"></div></div>`; }).join(''); document.getElementById('mainModal').classList.remove('hidden'); initSwiper(true, true, false, false); playSpeed = 8000; updateSpeedFromSlider(8000); document.getElementById('speedSlider').value = 8000; showToast('Ken Burns Mode','info'); }
function openMosaic() { if (imagesOnly.length === 0) { showToast('ไม่มีภาพ','warning'); return; } const g = document.getElementById('mosaicGrid'); g.innerHTML = imagesOnly.map(img => `<div class="aspect-square overflow-hidden cursor-pointer hover:opacity-80 transition" onclick="openSinglePreview('${img.path}','image','${img.name}')"><img src="${img.path}" class="w-full h-full object-cover" loading="lazy"></div>`).join(''); document.getElementById('mosaicModal').classList.remove('hidden'); }
function openCompareSlider() { if (imagesOnly.length < 2) { showToast('ต้องมีอย่างน้อย 2 ภาพ','warning'); return; } document.getElementById('cmpSliderA').selectedIndex = 0; document.getElementById('cmpSliderB').selectedIndex = 1; updateCompareSlider(); document.getElementById('compareSliderModal').classList.remove('hidden'); }
function updateCompareSlider() { const a = document.getElementById('cmpSliderA').value; const b = document.getElementById('cmpSliderB').value; const c = document.getElementById('compareSliderContainer'); c.innerHTML = `<div class="compare-slider" id="cmpSlider"><img src="${b}" alt="after"><div class="after"><img src="${a}" alt="before"></div><div class="handle" id="cmpHandle"></div></div>`; makeCompareSlider(); }
function makeCompareSlider() { const slider = document.getElementById('cmpSlider'); const handle = document.getElementById('cmpHandle'); const after = slider.querySelector('.after'); let isDown = false; const move = (clientX) => { const r = slider.getBoundingClientRect(); let p = ((clientX - r.left) / r.width) * 100; p = Math.max(0, Math.min(100, p)); handle.style.left = p + '%'; after.style.width = p + '%'; }; slider.addEventListener('mousedown', (e) => { isDown = true; move(e.clientX); }); document.addEventListener('mousemove', (e) => { if (isDown) move(e.clientX); }); document.addEventListener('mouseup', () => isDown = false); slider.addEventListener('touchstart', (e) => move(e.touches[0].clientX)); slider.addEventListener('touchmove', (e) => move(e.touches[0].clientX)); }
function openSunburst() { const svg = document.getElementById('sunburstSvg'); const legend = document.getElementById('sunburstLegend'); svg.innerHTML = ''; legend.innerHTML = ''; const colors = {image:'#ec4899',video:'#ef4444',audio:'#3b82f6',text:'#22c55e',pdf:'#dc2626',archive:'#eab308',doc:'#6366f1',other:'#9ca3af',folder:'#fbbf24'}; const total = Object.values(typeStats).reduce((s,t)=>s+t.size,0) || 1; let cum = -90; Object.entries(typeStats).forEach(([type, data]) => { const angle = (data.size / total) * 360; const sa = cum, ea = cum + angle; cum = ea; const r1=80, r2=140, cx=150, cy=150; const x1=cx+r2*Math.cos(sa*Math.PI/180), y1=cy+r2*Math.sin(sa*Math.PI/180); const x2=cx+r2*Math.cos(ea*Math.PI/180), y2=cy+r2*Math.sin(ea*Math.PI/180); const x3=cx+r1*Math.cos(ea*Math.PI/180), y3=cy+r1*Math.sin(ea*Math.PI/180); const x4=cx+r1*Math.cos(sa*Math.PI/180), y4=cy+r1*Math.sin(sa*Math.PI/180); const la = angle > 180 ? 1 : 0; const path = `M ${x1} ${y1} A ${r2} ${r2} 0 ${la} 1 ${x2} ${y2} L ${x3} ${y3} A ${r1} ${r1} 0 ${la} 0 ${x4} ${y4} Z`; const p = document.createElementNS('http://www.w3.org/2000/svg','path'); p.setAttribute('d',path); p.setAttribute('fill',colors[type]||'#888'); p.setAttribute('class','sunburst-arc'); p.setAttribute('stroke','var(--dark)'); p.setAttribute('stroke-width','2'); svg.appendChild(p); legend.innerHTML += `<div class="flex items-center gap-2"><span class="w-3 h-3 rounded" style="background:${colors[type]||'#888'}"></span><span class="text-th-text">${type}: ${formatSizeJS(data.size)} (${data.count})</span></div>`; }); const t = document.createElementNS('http://www.w3.org/2000/svg','text'); t.setAttribute('x',150); t.setAttribute('y',150); t.setAttribute('text-anchor','middle'); t.setAttribute('fill','var(--text)'); t.setAttribute('font-size','14'); t.setAttribute('font-weight','bold'); t.textContent = formatSizeJS(total); svg.appendChild(t); document.getElementById('sunburstModal').classList.remove('hidden'); }
function formatSizeJS(b) { if (b<1024) return b+' B'; if (b<1048576) return (b/1024).toFixed(1)+' KB'; if (b<1073741824) return (b/1048576).toFixed(1)+' MB'; return (b/1073741824).toFixed(2)+' GB'; }
function openGlobalSearch() { document.getElementById('globalSearchModal').classList.remove('hidden'); const inp = document.getElementById('globalSearchInput'); inp.value = ''; inp.focus(); inp.oninput = () => { const q = inp.value.toLowerCase().trim(); const r = document.getElementById('globalSearchResults'); if (!q) { r.innerHTML = '<div class="text-center text-th-text/50 py-8">พิมพ์เพื่อค้นหา...</div>'; return; } const matches = allFiles.filter(f => f.name.toLowerCase().includes(q)); if (matches.length === 0) { r.innerHTML = '<div class="text-center text-th-text/50 py-8">ไม่พบผลลัพธ์</div>'; return; } r.innerHTML = matches.map(f => `<div onclick="handleFileClick('${f.path}','${f.type}','${f.name}'); document.getElementById('globalSearchModal').classList.add('hidden');" class="flex items-center gap-3 p-3 rounded-lg bg-input-bg hover:border-primary border border-transparent cursor-pointer transition"><i class="fas ${fileTypeIcon(f.type)} text-2xl text-primary"></i><div class="flex-1 min-w-0"><div class="truncate font-semibold">${f.name}</div><div class="text-xs text-th-text/50">${f.type}${f.rating ? ' · ★'.repeat(f.rating) : ''}</div></div><i class="fas fa-chevron-right text-th-text/30"></i></div>`).join(''); }; inp.oninput(); }
function openComparator() { if (allFiles.length < 2) { showToast('ต้องมีอย่างน้อย 2 ไฟล์','warning'); return; } document.getElementById('compareA').selectedIndex = 0; document.getElementById('compareB').selectedIndex = 1; updateCompare(); document.getElementById('comparatorModal').classList.remove('hidden'); }
function updateCompare() { [['A','compareA','compareViewA'],['B','compareB','compareViewB']].forEach(([_, selId, viewId]) => { const sel = document.getElementById(selId); const path = sel.value; const type = sel.options[sel.selectedIndex].dataset.type; const v = document.getElementById(viewId); if (type === 'image') v.innerHTML = `<img src="${path}" class="w-full h-full object-contain">`; else if (type === 'video') v.innerHTML = `<video src="${path}" controls class="w-full h-full object-contain"></video>`; else v.innerHTML = `<div class="flex items-center justify-center h-full"><i class="fas ${fileTypeIcon(type)} text-4xl"></i></div>`; }); }
function openFavorites() { document.getElementById('favoritesModal').classList.remove('hidden'); }
function openFolderTree() { document.getElementById('folderTreeModal').classList.remove('hidden'); }
function openActivityLog() { document.getElementById('activityModal').classList.remove('hidden'); }
function showDuplicates() { showToast('ไฟล์ซ้ำอาจมีขนาดเท่ากัน ตรวจสอบก่อนลบ','warning'); }
function toggleZenMode() { isZenMode = !isZenMode; document.querySelectorAll('.glass').forEach(el => { if (el.closest('#mainModal') || el.closest('#commandPalette') || el.closest('#contextMenu')) return; el.style.transition = 'opacity .5s'; el.style.opacity = isZenMode ? '0.3' : '1'; }); showToast(isZenMode ? 'Zen Mode - เลื่อนเมาส์ที่การ์ดเพื่อโฟกัส' : 'ออกจาก Zen Mode','info'); }
function toggleVoiceControl() { const SR = window.SpeechRecognition || window.webkitSpeechRecognition; if (!SR) { showToast('เบราว์เซอร์ไม่รองรับ','error'); return; } if (isVoiceOn) { recognition?.stop(); isVoiceOn = false; document.getElementById('voiceBtn').classList.remove('bg-primary','text-btn-text'); showToast('ปิดเสียง','info'); return; } recognition = new SR(); recognition.lang = 'th-TH'; recognition.continuous = true; recognition.interimResults = false; recognition.onresult = (e) => { const last = e.results[e.results.length - 1]; const tr = last[0].transcript.trim().toLowerCase(); showToast('ได้ยิน: ' + tr,'info'); if (tr.includes('เล่น') || tr.includes('play')) { if (imagesOnly.length > 0) openImageSlideshow(); else openSlideshow(); } else if (tr.includes('หยุด') || tr.includes('stop')) closePlayer(); else if (tr.includes('วิดีโอ') || tr.includes('video')) openVideoPipeline(); else if (tr.includes('ค้นหา') || tr.includes('search')) document.getElementById('searchInput')?.focus(); else if (tr.includes('สามมิติ') || tr.includes('3d')) openCylinderGallery(); else if (tr.includes('เพลง') || tr.includes('audio')) openAudioPlayer(); else if (tr.includes('ปิด') || tr.includes('close')) document.querySelectorAll('[id$=Modal]').forEach(m => m.classList.add('hidden')); }; recognition.onend = () => { if (isVoiceOn) recognition.start(); }; recognition.start(); isVoiceOn = true; document.getElementById('voiceBtn').classList.add('bg-primary','text-btn-text'); showToast('เปิดเสียง - ลอง: "เล่น", "วิดีโอ", "หยุด", "ค้นหา", "เพลง"','success'); }
const commands = [
    {name:'เล่นภาพสไลด์',icon:'fa-images',action:openImageSlideshow,kw:['image','slideshow','รูป']},
    {name:'เล่นมีเดียทั้งหมด',icon:'fa-play-circle',action:openSlideshow,kw:['media','มีเดีย']},
    {name:'Video Pipeline',icon:'fa-film',action:openVideoPipeline,kw:['video','วิดีโอ','pipeline']},
    {name:'Audio Player',icon:'fa-headphones',action:openAudioPlayer,kw:['audio','เพลง','เสียง']},
    {name:'3D Cylinder Gallery',icon:'fa-circle-nodes',action:openCylinderGallery,kw:['3d','cylinder']},
    {name:'Ken Burns Mode',icon:'fa-film',action:openKenBurns,kw:['ken','burns']},
    {name:'Mosaic View',icon:'fa-th-large',action:openMosaic,kw:['mosaic','โมเสก']},
    {name:'Compare Slider',icon:'fa-arrows-left-right',action:openCompareSlider,kw:['compare','เปรียบเทียบ']},
    {name:'สรุปพื้นที่ (Sunburst)',icon:'fa-chart-pie',action:openSunburst,kw:['sunburst','สรุป','พื้นที่']},
    {name:'รายการโปรด',icon:'fa-star',action:openFavorites,kw:['favorite','โปรด']},
    {name:'เปรียบเทียบไฟล์',icon:'fa-columns',action:openComparator,kw:['compare','เปรียบเทียบ']},
    {name:'ค้นหาทั้งหมด',icon:'fa-globe',action:openGlobalSearch,kw:['global','ทั้งหมด']},
    {name:'Folder Tree',icon:'fa-sitemap',action:openFolderTree,kw:['tree','โครงสร้าง']},
    {name:'Activity Log',icon:'fa-history',action:openActivityLog,kw:['log','กิจกรรม']},
    {name:'Zen Mode',icon:'fa-spa',action:toggleZenMode,kw:['zen']},
    {name:'Voice Control',icon:'fa-microphone',action:toggleVoiceControl,kw:['voice','เสียง']},
    {name:'Compact Mode',icon:'fa-compress',action:toggleCompactMode,kw:['compact','เล็ก']},
    {name:'โหมดเลือกหลาย',icon:'fa-check-square',action:toggleSelectMode,kw:['select','เลือก']},
    {name:'สลับมุมมอง',icon:'fa-th',action:() => setView(viewMode === 'grid' ? 'list' : viewMode === 'list' ? 'filmstrip' : viewMode === 'filmstrip' ? 'detail' : viewMode === 'detail' ? 'masonry' : 'grid'),kw:['view','มุมมอง']},
];
function openCommandPalette() { document.getElementById('commandPalette').classList.remove('hidden'); const inp = document.getElementById('cmdInput'); inp.value = ''; inp.focus(); filterCommands(); }
function filterCommands() { const q = document.getElementById('cmdInput').value.toLowerCase().trim(); const r = document.getElementById('cmdResults'); const f = commands.filter(c => !q || c.name.toLowerCase().includes(q) || c.kw.some(k => k.includes(q))); if (f.length === 0) { r.innerHTML = '<div class="p-4 text-center text-th-text/50">ไม่พบคำสั่ง</div>'; return; } r.innerHTML = f.map((c, i) => `<div class="cmd-item flex items-center gap-3 p-3 rounded-lg cursor-pointer ${i === 0 ? 'active' : ''}" onclick="runCommand(${commands.indexOf(c)})"><i class="fas ${c.icon} text-primary w-5 text-center"></i><span class="flex-1">${c.name}</span></div>`).join(''); }
function runCommand(idx) { document.getElementById('commandPalette').classList.add('hidden'); commands[idx].action(); }
document.getElementById('cmdInput')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { const a = document.querySelector('.cmd-item.active'); if (a) a.click(); } else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); const items = Array.from(document.querySelectorAll('.cmd-item')); let idx = items.findIndex(i => i.classList.contains('active')); items[idx]?.classList.remove('active'); idx = e.key === 'ArrowDown' ? (idx + 1) % items.length : (idx - 1 + items.length) % items.length; items[idx]?.classList.add('active'); items[idx]?.scrollIntoView({block:'nearest'}); } });

const canvas = document.getElementById('particleCanvas');
const ctx = canvas.getContext('2d');
let particles = [];
function resizeCanvas() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
resizeCanvas(); window.addEventListener('resize', resizeCanvas);
function initParticles() { particles = []; for (let i = 0; i < 60; i++) particles.push({x: Math.random()*canvas.width, y: Math.random()*canvas.height, vx: (Math.random()-0.5)*0.5, vy: (Math.random()-0.5)*0.5, r: Math.random()*2+0.5}); }
initParticles();
function animateParticles() { ctx.clearRect(0, 0, canvas.width, canvas.height); const p = getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#00f0ff'; particles.forEach((pa, i) => { pa.x += pa.vx; pa.y += pa.vy; if (pa.x < 0 || pa.x > canvas.width) pa.vx *= -1; if (pa.y < 0 || pa.y > canvas.height) pa.vy *= -1; ctx.beginPath(); ctx.arc(pa.x, pa.y, pa.r, 0, Math.PI*2); ctx.fillStyle = p; ctx.globalAlpha = 0.6; ctx.fill(); for (let j = i+1; j < particles.length; j++) { const dx = pa.x - particles[j].x, dy = pa.y - particles[j].y, d = Math.sqrt(dx*dx + dy*dy); if (d < 120) { ctx.beginPath(); ctx.moveTo(pa.x, pa.y); ctx.lineTo(particles[j].x, particles[j].y); ctx.strokeStyle = p; ctx.globalAlpha = (1 - d/120) * 0.15; ctx.lineWidth = 1; ctx.stroke(); } } }); ctx.globalAlpha = 1; requestAnimationFrame(animateParticles); }
animateParticles();

const cursorDot = document.getElementById('cursorDot');
const cursorRing = document.getElementById('cursorRing');
let cursorEnabled = localStorage.getItem('cursorEnabled') !== 'false';
if (cursorEnabled) { cursorDot.style.display = 'block'; cursorRing.style.display = 'block'; document.body.style.cursor = 'none'; }
let ringX = 0, ringY = 0, targetX = 0, targetY = 0;
document.addEventListener('mousemove', (e) => { targetX = e.clientX; targetY = e.clientY; cursorDot.style.left = targetX + 'px'; cursorDot.style.top = targetY + 'px'; });
function animateRing() { ringX += (targetX - ringX) * 0.15; ringY += (targetY - ringY) * 0.15; cursorRing.style.left = ringX + 'px'; cursorRing.style.top = ringY + 'px'; requestAnimationFrame(animateRing); }
animateRing();
document.addEventListener('mouseover', (e) => { if (e.target.closest('button,a,input,select,.item-card,[onclick]')) { cursorRing.style.width = '50px'; cursorRing.style.height = '50px'; } else { cursorRing.style.width = '36px'; cursorRing.style.height = '36px'; } });

document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); openCommandPalette(); return; }
    const tag = (e.target.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') { if (e.key === 'Escape') e.target.blur(); return; }
    if (e.key === 'Escape') {
        if (!document.getElementById('mainModal').classList.contains('hidden')) { closePlayer(); return; }
        ['moveModal','folderMetaModal','fileInfoModal','commandPalette','cylinderModal','mosaicModal','sunburstModal','globalSearchModal','comparatorModal','favoritesModal','folderTreeModal','activityModal','compareSliderModal'].forEach(id => { const m = document.getElementById(id); if (m && !m.classList.contains('hidden')) m.classList.add('hidden'); });
        if (isSelectMode) toggleSelectMode(); return;
    }
    if (e.ctrlKey || e.altKey || e.metaKey) return;
    const videoVisible = !document.getElementById('videoControlsGroup').classList.contains('hidden');
    const audioVisible = !document.getElementById('audioVisualizer').classList.contains('hidden');
    switch (e.key.toLowerCase()) {
        case 'f': if (!videoVisible) { e.preventDefault(); document.getElementById('searchInput')?.focus(); } else if (activeVideoEl) { e.preventDefault(); toggleFullscreen(); } break;
        case 's': if (e.shiftKey && videoVisible) { e.preventDefault(); slowMoPreset(); } else if (imagesOnly.length > 0 && !videoVisible) { e.preventDefault(); openImageSlideshow(); } break;
        case 'p': if (mediaFiles.length > 0) { e.preventDefault(); openSlideshow(); } break;
        case 'v': if (videosOnly.length > 0) { e.preventDefault(); openVideoPipeline(); } break;
        case '3': if (imagesOnly.length > 0) { e.preventDefault(); openCylinderGallery(); } break;
        case 'o': if (imagesOnly.length > 0) { e.preventDefault(); openMosaic(); } break;
        case 'k': if (imagesOnly.length > 0) { e.preventDefault(); openKenBurns(); } break;
        case 'm': if (videoVisible && activeVideoEl) { e.preventDefault(); activeVideoEl.muted = !activeVideoEl.muted; } else { e.preventDefault(); toggleSelectMode(); } break;
        case 'z': if (videoVisible && activeVideoEl) { e.preventDefault(); digitalZoom(); } else { e.preventDefault(); toggleZenMode(); } break;
        case 't': if (videoVisible) { e.preventDefault(); toggleTheater(); } break;
        case 'j': if (videoVisible && activeVideoEl) { e.preventDefault(); videoSeek(-10); } break;
        case 'l': if (videoVisible && activeVideoEl) { e.preventDefault(); videoSeek(10); } break;
        case 'h': if (!document.getElementById('mainModal').classList.contains('hidden')) { e.preventDefault(); toggleUI(); } break;
        case 'delete': if (isSelectMode && selectedItems.size > 0) { e.preventDefault(); batchDelete(); } break;
        case ' ': if (!document.getElementById('mainModal').classList.contains('hidden')) { if (videoVisible && activeVideoEl) { e.preventDefault(); if (activeVideoEl.paused) activeVideoEl.play(); else activeVideoEl.pause(); } else if (audioVisible && audioElement) { e.preventDefault(); if (audioElement.paused) audioElement.play(); else audioElement.pause(); } else if (document.getElementById('slideshowControls').classList.contains('flex')) { e.preventDefault(); toggleAutoplay(); } } break;
        case 'arrowleft': if (videoVisible && activeVideoEl) { e.preventDefault(); videoSeek(-5); } else if (audioVisible && audioElement) { e.preventDefault(); audioElement.currentTime = Math.max(0, audioElement.currentTime - 5); } break;
        case 'arrowright': if (videoVisible && activeVideoEl) { e.preventDefault(); videoSeek(5); } else if (audioVisible && audioElement) { e.preventDefault(); audioElement.currentTime = Math.min(audioElement.duration, audioElement.currentTime + 5); } break;
        case 'arrowup': if (videoVisible && activeVideoEl) { e.preventDefault(); activeVideoEl.volume = Math.min(1, activeVideoEl.volume + 0.1); } else if (audioVisible && audioElement) { e.preventDefault(); audioElement.volume = Math.min(1, audioElement.volume + 0.1); } break;
        case 'arrowdown': if (videoVisible && activeVideoEl) { e.preventDefault(); activeVideoEl.volume = Math.max(0, activeVideoEl.volume - 0.1); } else if (audioVisible && audioElement) { e.preventDefault(); audioElement.volume = Math.max(0, audioElement.volume - 0.1); } break;
        case ',': if (videoVisible) { e.preventDefault(); videoFrameStep(-1); } break;
        case '.': if (videoVisible) { e.preventDefault(); videoFrameStep(1); } break;
    }
});

window.addEventListener('load', () => { setTimeout(() => { const ss = document.getElementById('sortSelect'); if (ss) { ss.value = sortMode; applySort(); } }, 100); });
</script>
</body>
</html>
