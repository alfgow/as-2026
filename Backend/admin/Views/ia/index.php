<?php
require_once __DIR__ . '/../../Helpers/url.php';

$iaHistorialUrl = admin_url('/ia/historial');
$iaChatUrl      = admin_url('/ia/chat');
?>
<!-- Chat Card -->
<link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
<script type="module">
    import {
        createChat
    } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

    createChat({
        webhookUrl: 'https://n8n.villanuevagarcia.com/webhook/0cc20c99-58a2-4c24-9cbf-f0b04be3d097/chat'
    });
</script>