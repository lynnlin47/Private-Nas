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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NAS Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media (max-width: 768px) {
            .flex.h-screen { flex-direction: column; }
            aside { width: 100% !important; border-right: none !important; border-bottom: 1px solid #1f2937; }
            main { padding: 1rem !important; }
        }
    </style>
</head>
<body class="bg-[#0f172a] text-white font-sans">
    <div class="flex h-screen">
        <!-- Sidebar เดียวกันกับ Dashboard -->
        <aside class="w-64 p-6 border-r border-gray-800">
            <h1 class="text-2xl font-bold mb-10 text-white">Control Panel</h1>
            <nav class="space-y-4">
                <a href="../" class="block py-2.5 px-4 rounded hover:bg-gray-800">Dashboard</a>
                <a href="./" class="block py-2.5 px-4 rounded bg-blue-600">Settings</a>
                <a href="../nas/" class="block py-2.5 px-4 rounded hover:bg-gray-800">GO NAS</a>
                <a href="../outweb/" class="block py-2.5 px-4 rounded hover:bg-gray-800">Out web</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10 overflow-y-auto">
            <header class="mb-8">
                <h2 class="text-3xl font-bold">System Settings</h2>
            </header>

            <form method="POST" class="bg-[#1e293b] p-6 rounded-xl border border-gray-700 max-w-4xl">
                <h3 class="text-xl font-bold mb-6">System Tools</h3>
                <div class="space-y-3">
                    <button name="action" value="open_firewall" class="w-full text-left py-3 px-4 bg-[#0f172a] rounded border border-gray-700 hover:bg-gray-800 transition">Windows Defender Firewall with Advanced Security</button>
                    <button name="action" value="open_cmd" class="w-full text-left py-3 px-4 bg-[#0f172a] rounded border border-gray-700 hover:bg-gray-800 transition">CMD</button>
                    <button name="action" value="open_ipconfig" class="w-full text-left py-3 px-4 bg-[#0f172a] rounded border border-gray-700 hover:bg-gray-800 transition">IP-Lan (ดูตรง Wireless LAN adapter Wi-Fi > IPv4)</button>
                    <button name="action" value="open_diskmgmt" class="w-full text-left py-3 px-4 bg-[#0f172a] rounded border border-gray-700 hover:bg-gray-800 transition">Disk Management</button>
                    <button name="action" value="open_services" class="w-full text-left py-3 px-4 bg-[#0f172a] rounded border border-gray-700 hover:bg-gray-800 transition">Services Management</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>