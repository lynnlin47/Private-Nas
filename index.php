<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'open_firewall':
                exec('start wf.msc');
                break;
            case 'open_cmd':
                exec('start cmd.exe');
                break;
            case 'open_ipconfig':
                exec('start cmd.exe /k ipconfig /all');
                break;
            case 'open_diskmgmt':
                exec('start diskmgmt.msc');
                break;
            case 'open_services':
                exec('start services.msc');
                break;
        }
    }
}
function getDriveInfo($driveLetter) {
    $path = $driveLetter . ':\\';
    if (!is_dir($path)) {
        return ['status' => 'not_found'];
    }
    
    $total = disk_total_space($path);
    $free = disk_free_space($path);
    $used = $total - $free;
    $percent = round(($used / $total) * 100);
    
    return [
        'status' => 'found',
        'total' => round($total / pow(1024, 3), 2),
        'used' => round($used / pow(1024, 3), 2),
        'percent' => $percent
    ];
}

function getLanIP() {
    $output = shell_exec('ipconfig');
    $pattern = '/Wireless LAN adapter Wi-Fi:.*IPv4 Address[ .]*: ([0-9.]+)/s';
    
    if (preg_match($pattern, $output, $matches)) {
        return $matches[1];
    }
    return '127.0.0.1';
}

$all_letters = range('A', 'Z');
$localhost = '127.0.0.1';
$lan_ip = getLanIP();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NAS Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 768px) {
            .flex.h-screen { flex-direction: column; }
            aside { width: 100% !important; border-right: none !important; border-bottom: 1px solid #1f2937; }
            main { padding: 1rem !important; }
            .grid-cols-1.md\:grid-cols-3 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .grid.grid-cols-1.md\:grid-cols-2 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
            .text-2xl { font-size: 1.25rem; }
        }
    </style>
</head>
<body class="bg-[#0f172a] text-white font-sans">
    <div class="flex h-screen">
        <aside class="w-64 p-6 border-r border-gray-800">
            <h1 class="text-2xl font-bold mb-10 text-white">Control Panel</h1>
            <nav class="space-y-4">
                <a href="./" class="block py-2.5 px-4 rounded bg-blue-600">Dashboard</a>
                <a href="./settings/" class="block py-2.5 px-4 rounded hover:bg-gray-800">Settings</a>
                <a href="./nas/" class="block py-2.5 px-4 rounded hover:bg-gray-800">GO NAS</a>
                <a href="./outweb/" class="block py-2.5 px-4 rounded hover:bg-gray-800">Out web</a>
            </nav>
        </aside>
        <main class="flex-1 p-10 overflow-y-auto">
            <header class="mb-8">
                <h2 class="text-3xl font-bold">System Overview</h2>
                <p class="text-gray-400">สถานะการทำงานของเซิร์ฟเวอร์ในขณะนี้</p>
            </header>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <?php foreach ($all_letters as $letter): ?>
                    <?php $info = getDriveInfo($letter); ?>
                    <div class="bg-[#1e293b] p-6 rounded-xl border border-gray-700">
                        <h3 class="text-gray-400 mb-2"><?php echo $letter; ?>: Usage</h3>
                        <?php if ($info['status'] === 'found'): ?>
                            <div class="text-2xl font-bold mb-2"><?php echo $info['used']; ?>/<?php echo $info['total']; ?> GB</div>
                            <div class="text-sm text-gray-300 mb-4"><?php echo $info['percent']; ?>%</div>
                            <div class="w-full bg-gray-900 h-2 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $info['percent']; ?>%"></div>
                            </div>
                        <?php else: ?>
                            <div class="text-red-500 font-medium">ไม่พบไดรฟ์ <?php echo $letter; ?>:</div>
                            <div class="text-gray-600 text-sm mt-2">ไม่ได้เชื่อมต่อในระบบ</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="bg-[#1e293b] p-6 rounded-xl border border-gray-700 mb-6">
                <h3 class="text-xl font-bold mb-6">MY PC</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex justify-between items-center bg-gray-900 p-4 rounded-lg">
                        <div>
                            <span class="text-gray-400 mr-4">Localhost</span>
                            <span class="font-mono text-xl"><?php echo $localhost; ?></span>
                        </div>
                        <button onclick="navigator.clipboard.writeText('<?php echo $localhost; ?>')" class="text-green-500 hover:underline">COPY IP</button>
                    </div>
                    <div class="flex justify-between items-center bg-gray-900 p-4 rounded-lg">
                        <div>
                            <span class="text-gray-400 mr-4">IP LAN (หากใช้ไม่ได้ให้ไปหน้า settings และ กด IP-Lan และหาดูตรง Wireless LAN adapter Wi-Fi > IPv4)</span>
                            <span class="font-mono text-xl text-green-400"><?php echo $lan_ip; ?></span>
                        </div>
                        <button onclick="navigator.clipboard.writeText('<?php echo $lan_ip; ?>')" class="text-green-500 hover:underline">COPY IP</button>
                    </div>
                </div>
            </div>
            <div class="bg-[#1e293b] p-6 rounded-xl border border-gray-700">
                <h3 class="text-xl font-bold mb-6">System Info</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-gray-900 p-4 rounded-lg">
                        <p class="text-gray-400 text-sm">Server Time</p>
                        <p class="text-lg font-bold"><?php echo date('d-m-Y'); ?></p>
                    </div>
                    <div class="bg-gray-900 p-4 rounded-lg">
                        <p class="text-gray-400 text-sm">PHP Version</p>
                        <p class="text-lg font-bold"><?php echo phpversion(); ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>