/**
 * Firebase Cloud Messaging Service Worker
 * Este archivo DEBE estar en la raíz del dominio para que funcione
 */

// Importar Firebase scripts
importScripts('https://www.gstatic.com/firebasejs/12.4.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.4.0/firebase-messaging-compat.js');

// Configuración de Firebase
firebase.initializeApp({
    apiKey: "AIzaSyDsOra9U9p9fzAigFrbxbj83KcBN7LdK6w",
    authDomain: "ciudadvirtual-48edd.firebaseapp.com",
    projectId: "ciudadvirtual-48edd",
    storageBucket: "ciudadvirtual-48edd.firebasestorage.app",
    messagingSenderId: "685228701255",
    appId: "1:685228701255:web:f76422bf30aadfc3056362"
});

// Inicializar Firebase Messaging
const messaging = firebase.messaging();

// Manejar mensajes en background (cuando el navegador está cerrado o minimizado)
messaging.onBackgroundMessage(function(payload) {
    console.log('[firebase-messaging-sw] Mensaje recibido en background:', payload);
    
    const notificationTitle = payload.notification.title || '🎟️ Nuevo Ticket';
    const notificationOptions = {
        body: payload.notification.body || 'Has recibido un nuevo ticket',
        icon: payload.notification.icon || '/wp-content/uploads/2024/03/cropped-logo_bajo_3.png',
        badge: payload.notification.badge || '/wp-content/uploads/2024/03/cropped-logo_bajo_3.png',
        tag: payload.notification.tag || 'ticket_notification',
        requireInteraction: true,
        vibrate: [200, 100, 200],
        data: payload.data || {},
        actions: [
            {
                action: 'view',
                title: 'Ver Ticket',
                icon: '/wp-content/uploads/2024/03/cropped-logo_bajo_3.png'
            },
            {
                action: 'close',
                title: 'Cerrar',
                icon: '/wp-content/uploads/2024/03/cropped-logo_bajo_3.png'
            }
        ]
    };
    
    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Manejar clicks en las notificaciones
self.addEventListener('notificationclick', function(event) {
    console.log('[firebase-messaging-sw] 🖱️ Notificación clickeada:', event);
    console.log('[firebase-messaging-sw] 📋 Datos de la notificación:', event.notification);
    console.log('[firebase-messaging-sw] 🎯 Data:', event.notification.data);
    console.log('[firebase-messaging-sw] 🔘 Action:', event.action);
    
    event.notification.close();
    
    var notificationData = event.notification.data || {};
    var action = event.action;
    
    console.log('[firebase-messaging-sw] 📦 notificationData extraído:', notificationData);
    
    // Abrir o enfocar la ventana y mostrar popup
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(function(clientList) {
                // Buscar si hay una ventana ya abierta
                console.log('[firebase-messaging-sw] 🔍 Buscando ventanas abiertas... Total:', clientList.length);
                
                for (var i = 0; i < clientList.length; i++) {
                    var client = clientList[i];
                    console.log('[firebase-messaging-sw] 🪟 Ventana encontrada:', client.url);
                    
                    if (client.url.indexOf('ciudadvirtual.app') !== -1) {
                        console.log('[firebase-messaging-sw] ✅ Ventana válida encontrada, enfocando...');
                        // Enfocar la ventana existente
                        return client.focus().then(function(focusedClient) {
                            console.log('[firebase-messaging-sw] 📤 Enviando mensaje al cliente...');
                            // Enviar mensaje al cliente para mostrar el popup
                            return focusedClient.postMessage({
                                type: 'SHOW_TICKET_POPUP',
                                data: notificationData,
                                action: action
                            });
                        });
                    }
                }
                
                // Si no hay ventana abierta, abrir una nueva
                if (clients.openWindow) {
                    return clients.openWindow('/store-manager/').then(function(newClient) {
                        // Esperar un poco para que cargue la página
                        return new Promise(function(resolve) {
                            setTimeout(function() {
                                newClient.postMessage({
                                    type: 'SHOW_TICKET_POPUP',
                                    data: notificationData,
                                    action: action
                                });
                                resolve();
                            }, 2000);
                        });
                    });
                }
            })
    );
});

console.log('[firebase-messaging-sw] Service Worker cargado y listo');

