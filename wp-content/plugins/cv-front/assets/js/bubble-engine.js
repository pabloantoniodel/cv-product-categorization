/**
 * Motor de Burbujas Animadas
 * Sistema de visualización de tiendas con burbujas flotantes
 * 
 * @package CV_Front
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Clase Bubble (Burbuja Individual)
     */
    class Bubble {
        constructor(store, canvasWidth, canvasHeight) {
            this.id = store.id;
            this.name = store.name;
            this.distance = store.distance;
            this.url = store.url;
            this.logo = store.logo;
            this.location = store.location;
            
            // Tamaño basado en distancia (más cerca = más grande)
            this.radius = this.calculateRadius(store.distance);
            
            // Posición inicial aleatoria
            this.x = Math.random() * (canvasWidth - this.radius * 2) + this.radius;
            this.y = Math.random() * (canvasHeight - this.radius * 2) + this.radius;
            
            // Velocidad aleatoria
            this.vx = (Math.random() - 0.5) * 0.5;
            this.vy = (Math.random() - 0.5) * 0.5;
            
            // Color basado en distancia
            this.color = this.getColorByDistance(store.distance);
            
            // Estado
            this.hovered = false;
            this.scale = 1;
            
            // Cargar imagen
            this.image = new Image();
            this.image.crossOrigin = 'Anonymous';
            this.image.src = store.logo;
            this.imageLoaded = false;
            this.image.onload = () => {
                this.imageLoaded = true;
            };
        }
        
        calculateRadius(distance) {
            // Más cercano = más grande (40-100px)
            const minRadius = 40;
            const maxRadius = 100;
            
            // Escala logarítmica
            const normalized = Math.log(distance + 1) / Math.log(10);
            const size = maxRadius - (normalized * (maxRadius - minRadius));
            
            return Math.max(minRadius, Math.min(maxRadius, size));
        }
        
        getColorByDistance(distance) {
            if (distance < 1) return '#43e97b';      // Verde
            if (distance < 3) return '#667eea';      // Morado
            if (distance < 5) return '#fa709a';      // Rosa
            return '#4facfe';                        // Azul
        }
        
        update(canvasWidth, canvasHeight, bubbles) {
            if (this.hovered) {
                // Si está en hover, solo animar escala
                this.scale = Math.min(1.15, this.scale + 0.05);
                return;
            }
            
            // Movimiento flotante
            this.x += this.vx;
            this.y += this.vy;
            
            // Rebote en bordes
            if (this.x - this.radius < 0 || this.x + this.radius > canvasWidth) {
                this.vx *= -1;
            }
            if (this.y - this.radius < 0 || this.y + this.radius > canvasHeight) {
                this.vy *= -1;
            }
            
            // Mantener dentro del canvas
            this.x = Math.max(this.radius, Math.min(canvasWidth - this.radius, this.x));
            this.y = Math.max(this.radius, Math.min(canvasHeight - this.radius, this.y));
            
            // Repulsión con otras burbujas
            bubbles.forEach(other => {
                if (other === this) return;
                
                const dx = other.x - this.x;
                const dy = other.y - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                const minDist = this.radius + other.radius + 10;
                
                if (distance < minDist && distance > 0) {
                    const force = (minDist - distance) / minDist * 0.5;
                    this.vx -= (dx / distance) * force;
                    this.vy -= (dy / distance) * force;
                }
            });
            
            // Limitar velocidad
            const maxSpeed = 1;
            const speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
            if (speed > maxSpeed) {
                this.vx = (this.vx / speed) * maxSpeed;
                this.vy = (this.vy / speed) * maxSpeed;
            }
            
            // Resetear escala si no está en hover
            this.scale = Math.max(1, this.scale - 0.05);
        }
        
        draw(ctx) {
            ctx.save();
            
            // Escala
            const scale = this.scale;
            const scaledRadius = this.radius * scale;
            
            // Sombra
            ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
            ctx.shadowBlur = 10 * scale;
            ctx.shadowOffsetY = 5 * scale;
            
            // Círculo de fondo
            ctx.beginPath();
            ctx.arc(this.x, this.y, scaledRadius, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
            
            // Borde
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 3 * scale;
            ctx.stroke();
            
            ctx.shadowColor = 'transparent';
            
            // Foto circular (clip)
            if (this.imageLoaded) {
                ctx.save();
                ctx.beginPath();
                ctx.arc(this.x, this.y, scaledRadius - 5, 0, Math.PI * 2);
                ctx.clip();
                
                const imgSize = (scaledRadius - 5) * 2;
                ctx.drawImage(
                    this.image,
                    this.x - scaledRadius + 5,
                    this.y - scaledRadius + 5,
                    imgSize,
                    imgSize
                );
                ctx.restore();
            }
            
            // Nombre
            ctx.fillStyle = '#333';
            ctx.font = 'bold ' + (12 * scale) + 'px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            
            const maxWidth = scaledRadius * 2;
            const name = this.truncateText(ctx, this.name, maxWidth);
            ctx.fillText(name, this.x, this.y + scaledRadius + 10);
            
            // Distancia
            ctx.fillStyle = this.color;
            ctx.font = (11 * scale) + 'px Arial';
            ctx.fillText(
                '📍 ' + this.distance + ' km',
                this.x,
                this.y + scaledRadius + 25
            );
            
            ctx.restore();
        }
        
        truncateText(ctx, text, maxWidth) {
            if (ctx.measureText(text).width <= maxWidth) {
                return text;
            }
            
            let truncated = text;
            while (ctx.measureText(truncated + '...').width > maxWidth && truncated.length > 0) {
                truncated = truncated.slice(0, -1);
            }
            return truncated + '...';
        }
        
        isPointInside(x, y) {
            const dx = x - this.x;
            const dy = y - this.y;
            return Math.sqrt(dx * dx + dy * dy) <= this.radius * this.scale;
        }
    }
    
    /**
     * Controlador Principal de Burbujas
     */
    class BubbleController {
        constructor(canvasId) {
            this.canvas = document.getElementById(canvasId);
            if (!this.canvas) {
                console.error('Canvas no encontrado:', canvasId);
                return;
            }
            
            this.ctx = this.canvas.getContext('2d');
            this.bubbles = [];
            this.animationId = null;
            this.hoveredBubble = null;
            
            this.setupCanvas();
            this.bindEvents();
            this.requestUserLocation();
            
            console.log('✅ CV Store Bubbles inicializado');
        }
        
        setupCanvas() {
            // Responsive canvas
            const container = this.canvas.parentElement;
            this.canvas.width = container.clientWidth;
            this.canvas.height = 600; // Altura fija inicial
            
            // Redimensionar al cambiar ventana
            window.addEventListener('resize', () => {
                this.canvas.width = container.clientWidth;
                this.canvas.height = 600;
            });
        }
        
        bindEvents() {
            const self = this;
            
            // Hover sobre canvas
            this.canvas.addEventListener('mousemove', (e) => {
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                self.handleHover(x, y);
            });
            
            // Click en canvas
            this.canvas.addEventListener('click', (e) => {
                const rect = this.canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                self.handleClick(x, y);
            });
            
            // Toggle de vista
            $('.cv-toggle-btn').on('click', function() {
                const view = $(this).data('view');
                self.switchView(view);
            });
            
            // Botón de ubicación
            $('.cv-btn-locate').on('click', () => {
                self.requestUserLocation();
            });
            
            // Cambio de radio
            $('#cv-radius-input').on('change', () => {
                const radius = parseInt($('#cv-radius-input').val());
                $('#cv-radius-value').val(radius);
                $('#cv-radius-display').text(radius);
                self.loadStores();
            });
        }
        
        requestUserLocation() {
            const self = this;
            
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización');
                return;
            }
            
            console.log('Solicitando ubicación del usuario...');
            $('.cv-bubbles-loading p').text('Obteniendo tu ubicación...');
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    console.log('✅ Ubicación obtenida:', position.coords.latitude, position.coords.longitude);
                    $('#cv-user-lat').val(position.coords.latitude);
                    $('#cv-user-lng').val(position.coords.longitude);
                    self.loadStores();
                },
                (error) => {
                    console.error('❌ Error de geolocalización:', error);
                    alert('No se pudo obtener tu ubicación. Usando ubicación por defecto.');
                    // Madrid por defecto
                    $('#cv-user-lat').val(40.416775);
                    $('#cv-user-lng').val(-3.703790);
                    self.loadStores();
                }
            );
        }
        
        loadStores() {
            const self = this;
            
            const lat = parseFloat($('#cv-user-lat').val());
            const lng = parseFloat($('#cv-user-lng').val());
            const radius = parseInt($('#cv-radius-value').val());
            const limit = parseInt($('#cv-limit-value').val());
            
            if (!lat || !lng) {
                console.error('Coordenadas no válidas');
                return;
            }
            
            console.log('Cargando tiendas...', {lat, lng, radius, limit});
            $('.cv-bubbles-loading').show();
            
            $.ajax({
                url: cvBubblesData.ajax_url,
                type: 'POST',
                data: {
                    action: 'cv_get_nearby_stores',
                    nonce: cvBubblesData.nonce,
                    lat: lat,
                    lng: lng,
                    radius: radius,
                    limit: limit
                },
                success: function(response) {
                    if (response.success) {
                        console.log('✅ Tiendas cargadas:', response.data.total);
                        self.initBubbles(response.data.stores);
                        $('#cv-stores-count').text(response.data.total);
                    } else {
                        console.error('Error:', response.data.message);
                        $('.cv-bubbles-loading p').text('Error al cargar tiendas');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error);
                    $('.cv-bubbles-loading p').text('Error de conexión');
                }
            });
        }
        
        initBubbles(stores) {
            this.bubbles = stores.map(store => 
                new Bubble(store, this.canvas.width, this.canvas.height)
            );
            
            $('.cv-bubbles-loading').hide();
            this.startAnimation();
        }
        
        startAnimation() {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
            }
            
            const animate = () => {
                this.update();
                this.render();
                this.animationId = requestAnimationFrame(animate);
            };
            
            animate();
        }
        
        stopAnimation() {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
        }
        
        update() {
            this.bubbles.forEach(bubble => {
                bubble.update(this.canvas.width, this.canvas.height, this.bubbles);
            });
        }
        
        render() {
            // Limpiar canvas
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            
            // Fondo
            this.ctx.fillStyle = '#f8f9fa';
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
            
            // Dibujar burbujas (más lejanas primero)
            const sorted = [...this.bubbles].sort((a, b) => b.distance - a.distance);
            sorted.forEach(bubble => bubble.draw(this.ctx));
        }
        
        handleHover(x, y) {
            let found = false;
            
            for (let bubble of this.bubbles) {
                if (bubble.isPointInside(x, y)) {
                    bubble.hovered = true;
                    this.hoveredBubble = bubble;
                    this.showTooltip(bubble, x, y);
                    this.canvas.style.cursor = 'pointer';
                    found = true;
                } else {
                    bubble.hovered = false;
                }
            }
            
            if (!found) {
                this.hoveredBubble = null;
                this.hideTooltip();
                this.canvas.style.cursor = 'default';
            }
        }
        
        handleClick(x, y) {
            for (let bubble of this.bubbles) {
                if (bubble.isPointInside(x, y)) {
                    console.log('Click en tienda:', bubble.name);
                    window.location.href = bubble.url;
                    break;
                }
            }
        }
        
        showTooltip(bubble, x, y) {
            const $tooltip = $('#cv-bubble-tooltip');
            
            $tooltip.find('.cv-tooltip-photo').attr('src', bubble.logo);
            $tooltip.find('.cv-tooltip-name').text(bubble.name);
            $tooltip.find('.cv-tooltip-distance').text('📍 ' + bubble.distance + ' km de distancia');
            $tooltip.find('.cv-tooltip-location').text(bubble.location || '');
            $tooltip.data('url', bubble.url);
            
            // Posicionar tooltip
            const tooltipWidth = 280;
            const tooltipHeight = 200;
            let left = x + 20;
            let top = y - tooltipHeight / 2;
            
            // Ajustar si se sale del canvas
            if (left + tooltipWidth > this.canvas.width) {
                left = x - tooltipWidth - 20;
            }
            if (top < 0) top = 10;
            if (top + tooltipHeight > this.canvas.height) {
                top = this.canvas.height - tooltipHeight - 10;
            }
            
            $tooltip.css({
                left: left + 'px',
                top: top + 'px',
                display: 'block'
            });
        }
        
        hideTooltip() {
            $('#cv-bubble-tooltip').hide();
        }
        
        switchView(view) {
            $('.cv-toggle-btn').removeClass('active');
            $('.cv-toggle-btn[data-view="' + view + '"]').addClass('active');
            
            if (view === 'bubbles') {
                $('#cv-bubbles-view').show();
                $('#cv-map-view').hide();
                this.startAnimation();
            } else {
                $('#cv-bubbles-view').hide();
                $('#cv-map-view').show();
                this.stopAnimation();
            }
        }
    }
    
    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        if ($('#cv-bubbles-canvas').length > 0) {
            window.bubbleController = new BubbleController('cv-bubbles-canvas');
            
            // Click en botón del tooltip
            $(document).on('click', '.cv-tooltip-btn', function() {
                const url = $('#cv-bubble-tooltip').data('url');
                if (url) {
                    window.location.href = url;
                }
            });
        }
    });
    
})(jQuery);




