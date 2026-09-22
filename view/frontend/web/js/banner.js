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

        function buildCounter(banner) {
            var counter = banner.counter || {};
            var bannerId = parseInt(banner.id, 10);
            var backgroundColor = firstValue(counter.background_color, banner.counter_bg_color, '#000000');
            var textColor = firstValue(counter.text_color, banner.counter_color_text, '#FFFFFF');
            var $counter, $numbers;
            var units = [['days', 'Dias'], ['hours', 'HRS'], ['minutes', 'MIN'], ['seconds', 'SECS']];

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
                if (index > 0) {
                    $numbers.append($('<span/>', {'class': 'leanzote-counter__separator', text: '|'}));
                }
                $numbers.append(
                    $('<div/>', {'class': 'leanzote-counter__time-block'})
                        .append($('<span/>', {'class': 'leanzote-counter__' + unit[0], text: '00'}))
                        .append($('<span/>', {'class': 'leanzote-counter__unit', text: unit[1]}))
                );
            });
            $counter.append($numbers);
            setStyle($counter.find('span'), 'color', textColor, true);
            return $counter;
        }

        function getMessages(banner) {
            var messages = $.isArray(banner.messages) ? banner.messages : [];
            if (!messages.length && banner.content) {
                messages = [{desktop: banner.content, mobile: banner.content}];
            }
            return messages.slice(0, 3);
        }

        function getMessageText(message) {
            var mobile = window.matchMedia('(max-width: ' + MOBILE_BREAKPOINT + 'px)').matches;
            return mobile ? firstValue(message.mobile, message.desktop) : firstValue(message.desktop, message.mobile);
        }

        function updateSlide($banner, index) {
            var messages = $banner.data('messages') || [];
            var total = messages.length;
            var safeIndex;
            if (!total) { return; }
            safeIndex = ((index % total) + total) % total;
            $banner.data('slide-index', safeIndex);
            $banner.find('.leanzote-banner__text').stop(true, true).fadeOut(120, function () {
                $(this).text(getMessageText(messages[safeIndex])).fadeIn(180, function () {
                    checkBannerVisibility();
                });
            });
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
            var messages = getMessages(banner);
            var $area = $('<div/>', {'class': 'leanzote-banner__message-area'});
            var $text = $('<span/>', {'class': 'leanzote-banner__text'});
            var $previous, $next;

            if (messages.length) {
                $text.text(getMessageText(messages[0]));
            }
            if (messages.length > 1) {
                $previous = $('<button/>', {
                    type: 'button',
                    'class': 'leanzote-banner__nav leanzote-banner__nav--prev',
                    'aria-label': 'Mensaje anterior',
                    html: '&#10094;'
                });
                $next = $('<button/>', {
                    type: 'button',
                    'class': 'leanzote-banner__nav leanzote-banner__nav--next',
                    'aria-label': 'Mensaje siguiente',
                    html: '&#10095;'
                });
                $area.append($previous);
            }
            $area.append($text);
            if (messages.length > 1) { $area.append($next); }
            return $area;
        }

        function buildBanner(banner, index) {
            var bannerId = parseInt(banner.id, 10);
            var buttonBefore = !!(banner.button && banner.button.before_counter);
            var backgroundColor = firstValue(banner.background_color, '#FFFFFF');
            var textColor = firstValue(banner.text_color, '#333333');
            var messages = getMessages(banner);
            var $content = $('<div/>', {'class': 'leanzote-banner__content'});
            var $actions = $('<div/>', {'class': 'leanzote-banner__actions'});
            var $button = buildButton(banner);
            var $counter = buildCounter(banner);
            var $bannerElement, $closeButton;

            if (buttonBefore) { $actions.addClass('button-before'); }
            $content.append(buildMessageArea(banner));
            if (buttonBefore) {
                $actions.append($button).append($counter);
            } else {
                $actions.append($counter).append($button);
            }
            $content.append($actions);

            $bannerElement = $('<div/>', {
                id: 'leanzote-banner-' + bannerId,
                'class': 'leanzote-banner'
            }).css({top: (index * 60) + 'px', display: 'none'}).data({
                'banner-id': bannerId,
                'end-date': banner.end_date || null,
                'messages': messages,
                'slide-index': 0,
                'carousel-interval': parseInt(banner.carousel_interval, 10) || 5
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
            setStyle($content.find('.leanzote-banner__text'), 'color', textColor, true);
            setStyle($content.find('.leanzote-banner__nav'), 'color', textColor, true);
            setStyle($closeButton, 'color', textColor, true);
            return $bannerElement.append($content).append($closeButton);
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
            $counter.find('.leanzote-counter__days').text(String(Math.floor(diff / 86400000)).padStart(2, '0'));
            $counter.find('.leanzote-counter__hours').text(String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0'));
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

        $root.on('click', '.leanzote-banner__nav', function () {
            var $banner = $(this).closest('.leanzote-banner');
            var current = parseInt($banner.data('slide-index'), 10) || 0;
            var direction = $(this).hasClass('leanzote-banner__nav--prev') ? -1 : 1;
            updateSlide($banner, current + direction);
            startCarousel($banner);
        });

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
                    $banner.find('.leanzote-banner__text').text(getMessageText(messages[index]));
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
