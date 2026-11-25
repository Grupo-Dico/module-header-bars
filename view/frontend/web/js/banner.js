define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var tickerId = null;
        var carouselIds = {};
        var DEFAULT_CAROUSEL_DELAY = 5000;
        var MOBILE_BREAKPOINT = 767;

        function getClosedKey(bannerId) {
            return 'leanzote_banner_closed_' + bannerId;
        }

        function isBannerClosed(bannerId) {
            bannerId = parseInt(bannerId, 10);
            if (!bannerId) { return false; }
            try {
                return window.sessionStorage.getItem(getClosedKey(bannerId)) === '1';
            } catch (error) {
                return false;
            }
        }

        function markBannerClosed(bannerId) {
            bannerId = parseInt(bannerId, 10);
            if (!bannerId) { return false; }
            try {
                window.sessionStorage.setItem(getClosedKey(bannerId), '1');
            } catch (error) {
                return false;
            }
            return true;
        }

        function parseDate(value) {
            var timestamp;
            if (!value) { return null; }
            timestamp = Date.parse(value);
            return isNaN(timestamp) ? null : timestamp;
        }

        function firstValue() {
            var index;
            for (index = 0; index < arguments.length; index++) {
                if (arguments[index] !== undefined && arguments[index] !== null && arguments[index] !== '') {
                    return arguments[index];
                }
            }
            return '';
        }

        function setStyle($element, property, value, important) {
            if (!value) { return $element; }
            $element.each(function () {
                this.style.setProperty(property, value, important ? 'important' : '');
            });
            return $element;
        }

        function applyColors($element, backgroundColor, textColor) {
            setStyle($element, 'background-color', backgroundColor, true);
            setStyle($element, 'color', textColor, true);
            return $element;
        }

        function clearTicker() {
            if (tickerId) {
                window.clearInterval(tickerId);
                tickerId = null;
            }
        }

        function clearCarousel(bannerId) {
            if (carouselIds[bannerId]) {
                window.clearInterval(carouselIds[bannerId]);
                delete carouselIds[bannerId];
            }
        }

        function clearAllCarousels() {
            $.each(carouselIds, function (bannerId, id) {
                window.clearInterval(id);
            });
            carouselIds = {};
        }

        function updateLayoutOffsets(totalHeight) {
            var offset = totalHeight > 0 ? totalHeight + 'px' : '0';
            var $stickyHeader = $('header.page-header.sticky:visible, .page-header.sticky:visible').first();
            var stickyHeaderHeight = $stickyHeader.length ? $stickyHeader.outerHeight() : 0;

            document.documentElement.style.setProperty('--leanzote-banner-stack-height', offset);
            document.documentElement.style.setProperty('--leanzote-sticky-header-height', stickyHeaderHeight + 'px');
            $('body').toggleClass('leanzote-banner-visible', totalHeight > 0).css('margin-top', offset);
        }

        function shouldShowBanner($banner) {
            var bannerId = $banner.data('banner-id');
            var endDate = parseDate($banner.data('end-date'));
            var now = Date.now();
            if (isBannerClosed(bannerId)) { return false; }
            if (endDate && now >= endDate) { return false; }
            return true;
        }

        function showBanner($banner) {
            if (!$banner.is(':visible')) {
                $banner.stop(true, true).css('display', 'flex').hide().fadeIn(300);
            }
        }

        function checkBannerVisibility() {
            var totalHeight = 0;
            var visibleBanners = 0;

            $root.find('.leanzote-banner').each(function () {
                var $banner = $(this);
                if (!shouldShowBanner($banner)) {
                    $banner.hide();
                    return;
                }
                $banner.css('top', totalHeight + 'px');
                showBanner($banner);
                totalHeight += $banner.outerHeight();
                visibleBanners++;
            });
            updateLayoutOffsets(visibleBanners > 0 ? totalHeight : 0);
        }

        function buildButton(banner) {
            var button = banner.button || {};
            var backgroundColor = firstValue(button.background_color, banner.button_color_background, '#000000');
            var textColor = firstValue(button.text_color, banner.button_color_text, '#FFFFFF');
            if (!button.enabled || !button.text || !button.link) { return $(); }
            return applyColors($('<a/>', {
                'class': 'leanzote-banner__button',
                href: button.link,
                text: button.text
            }), backgroundColor, textColor);
        }

        function isCounterActive(counter) {
            var startDate, endDate, now;
            if (!counter || !counter.enabled || !counter.end_date) { return false; }
            startDate = parseDate(counter.start_date);
            endDate = parseDate(counter.end_date);
            now = Date.now();
            if (startDate && now < startDate) { return false; }
            return !!endDate && now < endDate;
        }

        /**
         * Crea el elemento de un número (horas, minutos o segundos). Si hay un formato de muestra definido
         * en el Admin, el primer número de la muestra se reemplaza por el valor del contador conservando
         * el formato (tipo de letra, tamaño, estilo y color) que lo envuelve.
         */
        function buildCounterNumber(unitName, sampleHtml) {
            var $value = $('<span/>', {'class': 'leanzote-counter__' + unitName, text: '00'});
            var $wrapper, done = false;

            if (!sampleHtml) { return $value; }

            $wrapper = $('<span/>', {'class': 'leanzote-counter__formatted'}).html(sampleHtml);

            (function walk(node) {
                $(node).contents().each(function () {
                    var match, before, after;
                    if (done) { return false; }
                    if (this.nodeType === 3) {
                        match = /\d+/.exec(this.nodeValue);
                        if (match) {
                            before = this.nodeValue.slice(0, match.index);
                            after = this.nodeValue.slice(match.index + match[0].length);
                            this.nodeValue = before;
                            $(this).after(document.createTextNode(after)).after($value);
                            done = true;
                            return false;
                        }
                    } else if (this.nodeType === 1) {
                        walk(this);
                    }
                });
            }($wrapper[0]));

            return done ? $wrapper : $value;
        }

        function buildCounter(banner) {
            var counter = banner.counter || {};
            var bannerId = parseInt(banner.id, 10);
            var backgroundColor = firstValue(counter.background_color, banner.counter_bg_color, '#000000');
            var textColor = firstValue(counter.text_color, banner.counter_color_text, '#FFFFFF');
            var $counter, $numbers;
            var units = [['hours', 'HRS'], ['minutes', 'MIN'], ['seconds', 'SEGS']];

            if (!isCounterActive(counter)) { return $(); }

            $counter = applyColors($('<div/>', {
                id: 'banner-counter-' + bannerId,
                'class': 'leanzote-banner__counter leanzote-banner__counter--hidden'
            }), backgroundColor, textColor).data({
                'start-date': counter.start_date || null,
                'end-date': counter.end_date || null
            });

            $numbers = $('<div/>', {'class': 'leanzote-counter__numbers'});
            $.each(units, function (index, unit) {
                var $number = buildCounterNumber(unit[0], counter.numbers_format);
                if (index > 0) {
                    $numbers.append($('<span/>', {'class': 'leanzote-counter__separator', text: ':'}));
                }
                $numbers.append(
                    $('<div/>', {'class': 'leanzote-counter__time-block'})
                        .append($number)
                        .append($('<span/>', {'class': 'leanzote-counter__unit', text: unit[1]}))
                );
            });

            // Texto opcional con formato que acompaña al contador (HTML saneado en el servidor).
            if (counter.text) {
                $counter.append($('<div/>', {'class': 'leanzote-counter__text'}).html(counter.text));
            }
            $counter.append($numbers);

            // Los colores de Apariencia solo se fuerzan en elementos propios; el color definido en el
            // editor (en el texto o en el formato de los números) tiene prioridad sobre ellos.
            setStyle($counter.find('.leanzote-counter__separator, .leanzote-counter__unit'), 'color', textColor, true);
            if (!counter.numbers_format) {
                setStyle($counter.find('.leanzote-counter__time-block > span:first-child'), 'color', textColor, true);
            }
            return $counter;
        }

        function getMessages(banner) {
            var messages = $.isArray(banner.messages) ? banner.messages : [];
            if (!messages.length && banner.content) {
                messages = [{desktop: banner.content, mobile: banner.content}];
            }
            return messages.slice(0, 3);
        }

        function getMessageVariant(message) {
            var mobile = window.matchMedia('(max-width: ' + MOBILE_BREAKPOINT + 'px)').matches;
            var preferred = mobile ? message.mobile : message.desktop;
            var fallback = mobile ? message.desktop : message.mobile;

            // Compatibilidad con payload 1.0.6 (desktop/mobile como strings).
            if (typeof preferred === 'string') {
                return {text: preferred || (typeof fallback === 'string' ? fallback : ''), button: {}};
            }
            if (!preferred || typeof preferred !== 'object') {
                preferred = fallback && typeof fallback === 'object' ? fallback : {};
            }

            return {
                text: firstValue(preferred.text, fallback && fallback.text, ''),
                button: preferred.button || {}
            };
        }

        function renderSlide($banner, index, animate) {
            var messages = $banner.data('messages') || [];
            var total = messages.length;
            var safeIndex, variant, $body, $copy, $button;
            var buttonBackground = firstValue($banner.data('button-background-color'), '#000000');
            var buttonTextColor = firstValue($banner.data('button-text-color'), '#FFFFFF');

            if (!total) { return; }
            safeIndex = ((index % total) + total) % total;
            variant = getMessageVariant(messages[safeIndex]);
            $banner.data('slide-index', safeIndex);

            $body = $banner.find('.leanzote-banner__message-body');
            $copy = $body.find('.leanzote-banner__copy');
            $button = $body.find('.leanzote-banner__slide-button');

            function applyContent() {
                // El HTML (formato y emojis) viene saneado desde el servidor.
                $copy.html(variant.text || '').toggle(!!variant.text);
                $copy.parent().toggle(!!variant.text);

                if (variant.button && variant.button.text && variant.button.link) {
                    $button.attr('href', variant.button.link).html(variant.button.text).show();
                    applyColors($button, buttonBackground, buttonTextColor);
                } else {
                    $button.removeAttr('href').empty().hide();
                }
                checkBannerVisibility();
            }

            if (animate) {
                $body.stop(true, true).fadeOut(100, function () {
                    applyContent();
                    $body.fadeIn(140);
                });
            } else {
                applyContent();
            }
        }

        function updateSlide($banner, index) {
            renderSlide($banner, index, true);
        }

        function startCarousel($banner) {
            var bannerId = parseInt($banner.data('banner-id'), 10);
            var messages = $banner.data('messages') || [];
            clearCarousel(bannerId);
            if (messages.length <= 1) { return; }
            var intervalSeconds = parseInt($banner.data('carousel-interval'), 10);
            var delay = (!isNaN(intervalSeconds) && intervalSeconds >= 2 && intervalSeconds <= 60)
                ? intervalSeconds * 1000
                : DEFAULT_CAROUSEL_DELAY;

            carouselIds[bannerId] = window.setInterval(function () {
                updateSlide($banner, (parseInt($banner.data('slide-index'), 10) || 0) + 1);
            }, delay);
        }

        function buildMessageArea(banner) {
            var $area = $('<div/>', {'class': 'leanzote-banner__message-area'});
            var $body = $('<div/>', {'class': 'leanzote-banner__message-body'});
            var $copyGroup = $('<span/>', {'class': 'leanzote-banner__copy-group'});
            var $copy = $('<span/>', {'class': 'leanzote-banner__copy'});
            var $button = $('<a/>', {'class': 'leanzote-banner__slide-button'}).hide();

            $copyGroup.append($copy);
            $body.append($copyGroup).append($button);

            $area.append($body);
            return $area;
        }

        function buildBanner(banner, index) {
            var bannerId = parseInt(banner.id, 10);
            var backgroundColor = firstValue(banner.background_color, '#FFFFFF');
            var textColor = firstValue(banner.text_color, '#333333');
            var button = banner.button || {};
            var buttonBackgroundColor = firstValue(button.background_color, banner.button_color_background, '#000000');
            var buttonTextColor = firstValue(button.text_color, banner.button_color_text, '#FFFFFF');
            var messages = getMessages(banner);
            var $content = $('<div/>', {'class': 'leanzote-banner__content'});
            var $counter = buildCounter(banner);
            var $bannerElement, $closeButton;

            // Mensaje, contador y CTA comparten una misma fila centrada (sin columnas laterales).
            $content.append(buildMessageArea(banner));
            if (button.before_counter) {
                $content.find('.leanzote-banner__slide-button').after($counter);
            } else {
                $content.find('.leanzote-banner__slide-button').before($counter);
            }

            $bannerElement = $('<div/>', {
                id: 'leanzote-banner-' + bannerId,
                'class': 'leanzote-banner'
            }).css({top: (index * 60) + 'px', display: 'none'}).data({
                'banner-id': bannerId,
                'end-date': banner.end_date || null,
                'messages': messages,
                'slide-index': 0,
                'carousel-interval': parseInt(banner.carousel_interval, 10) || 5,
                'button-background-color': buttonBackgroundColor,
                'button-text-color': buttonTextColor
            });

            $closeButton = $('<button/>', {
                'class': 'leanzote-banner__close',
                'data-banner-id': bannerId,
                type: 'button',
                'aria-label': 'Cerrar promoción',
                html: '&times;'
            });

            applyColors($bannerElement, backgroundColor, textColor);
            setStyle($content, 'color', textColor, true);
            setStyle($content.find('.leanzote-banner__copy'), 'color', textColor, true);
            setStyle($closeButton, 'color', textColor, true);

            $bannerElement.append($content).append($closeButton);
            renderSlide($bannerElement, 0, false);
            return $bannerElement;
        }

        function setCounterVisible($counter, visible) {
            $counter.toggleClass('leanzote-banner__counter--hidden', !visible).css('display', '');
        }

        function updateCounter($counter) {
            var startDate = parseDate($counter.data('start-date'));
            var endDate = parseDate($counter.data('end-date'));
            var now = Date.now();
            var diff;
            if (startDate && now < startDate) { setCounterVisible($counter, false); return; }
            if (!endDate || now >= endDate) { setCounterVisible($counter, false); return; }
            diff = endDate - now;
            setCounterVisible($counter, true);
            // Sin escala de días: las horas acumulan el total restante.
            $counter.find('.leanzote-counter__hours').text(String(Math.floor(diff / 3600000)).padStart(2, '0'));
            $counter.find('.leanzote-counter__minutes').text(String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0'));
            $counter.find('.leanzote-counter__seconds').text(String(Math.floor((diff % 60000) / 1000)).padStart(2, '0'));
        }

        function tick() {
            $root.find('.leanzote-banner__counter').each(function () { updateCounter($(this)); });
            checkBannerVisibility();
        }

        function renderBanners(banners) {
            var renderedCount = 0;
            clearAllCarousels();
            $root.empty();
            if (!banners.length) {
                clearTicker();
                updateLayoutOffsets(0);
                return;
            }

            $.each(banners, function (index, banner) {
                var $banner;
                if (isBannerClosed(banner.id)) { return; }
                $banner = buildBanner(banner, renderedCount);
                $root.append($banner);
                startCarousel($banner);
                renderedCount++;
            });

            if (!renderedCount) {
                clearTicker();
                updateLayoutOffsets(0);
                return;
            }
            tick();
            clearTicker();
            tickerId = window.setInterval(tick, 1000);
        }

        $root.on('mouseenter', '.leanzote-banner', function () {
            clearCarousel($(this).data('banner-id'));
        });

        $root.on('mouseleave', '.leanzote-banner', function () {
            startCarousel($(this));
        });

        $root.on('click', '.leanzote-banner__close', function () {
            var bannerId = $(this).data('banner-id');
            var $banner = $('#leanzote-banner-' + bannerId);
            markBannerClosed(bannerId);
            clearCarousel(bannerId);
            $banner.fadeOut(300, function () {
                $banner.remove();
                if (!$root.find('.leanzote-banner').length) { clearTicker(); }
                checkBannerVisibility();
            });
        });

        $(window).on('resize.leanzoteBanner', function () {
            $root.find('.leanzote-banner').each(function () {
                var $banner = $(this);
                var messages = $banner.data('messages') || [];
                var index = parseInt($banner.data('slide-index'), 10) || 0;
                if (messages[index]) {
                    renderSlide($banner, index, false);
                }
            });
            checkBannerVisibility();
        });

        $.ajax({
            url: config.endpointUrl,
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: {current_path: window.location.pathname}
        }).done(function (response) {
            renderBanners(response && response.success && $.isArray(response.banners) ? response.banners : []);
        }).fail(function () {
            renderBanners([]);
        });
    };
});
