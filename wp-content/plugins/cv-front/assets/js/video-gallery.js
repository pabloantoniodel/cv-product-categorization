/**
 * CV Video Gallery JavaScript
 * Maneja la reproducción de videos de YouTube en modal
 */

(function($) {
    'use strict';
    
    let player = null;
    let currentVideoId = null;
    
    $(document).ready(function() {
        // Cargar API de YouTube
        loadYouTubeAPI();
        
        // Eventos de click en videos
        $('.cv-video-item, .cv-video-watch-btn').on('click', function(e) {
            e.preventDefault();
            const videoId = $(this).closest('.cv-video-item').data('video-id') || $(this).data('video-id');
            if (videoId) {
                openVideoModal(videoId);
            }
        });
        
        // Cerrar modal - Con prevención de propagación
        $('.cv-video-modal-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeVideoModal();
        });
        
        $('.cv-video-modal-overlay').on('click', function(e) {
            e.preventDefault();
            closeVideoModal();
        });
        
        // Cerrar con ESC
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#cv-video-modal').hasClass('active')) {
                closeVideoModal();
            }
        });
    });
    
    /**
     * Cargar API de YouTube
     */
    function loadYouTubeAPI() {
        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
    }
    
    /**
     * Abrir modal con video
     */
    function openVideoModal(videoId) {
        currentVideoId = videoId;
        $('#cv-video-modal').addClass('active');
        $('body').css('overflow', 'hidden');
        
        // Esperar a que la API de YouTube esté lista
        const checkYTReady = setInterval(function() {
            if (typeof YT !== 'undefined' && typeof YT.Player !== 'undefined') {
                clearInterval(checkYTReady);
                initPlayer(videoId);
            }
        }, 100);
    }
    
    /**
     * Inicializar reproductor de YouTube
     */
    function initPlayer(videoId) {
        // Destruir reproductor anterior si existe
        if (player) {
            player.destroy();
        }
        
        // Crear nuevo reproductor
        player = new YT.Player('cv-video-player', {
            videoId: videoId,
            playerVars: {
                autoplay: 0,
                rel: 0,
                modestbranding: 1,
                fs: 1,
                cc_load_policy: 0,
                iv_load_policy: 3,
                autohide: 1
            },
            events: {
                'onReady': onPlayerReady
            }
        });
    }
    
    /**
     * Cuando el reproductor está listo
     */
    function onPlayerReady(event) {
        // No hacer autoplay, dejar que el usuario de play manualmente
    }
    
    /**
     * Cerrar modal
     */
    function closeVideoModal() {
        $('#cv-video-modal').removeClass('active');
        $('body').css('overflow', '');
        
        // Detener y destruir reproductor
        if (player) {
            player.stopVideo();
            player.destroy();
            player = null;
        }
        
        // Limpiar contenedor
        $('#cv-video-player').html('');
        currentVideoId = null;
    }
    
})(jQuery);

