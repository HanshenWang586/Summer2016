/*
 * Fresco - A Beautiful Responsive Lightbox - v1.3.0
 * (c) 2012-2013 Nick Stakenburg
 *
 * http://www.frescojs.com
 *
 * License: http://www.frescojs.com/license
 */
(function ($) {
    var q = window.Fresco || {};
    $.extend(q, {
        version: "1.3.0"
    });
    q.skins = {
        base: {
            effects: {
                content: {
                    show: 0,
                    hide: 0,
                    move: 350,
                    sync: true
                },
                loading: {
                    show: 0,
                    hide: 300,
                    delay: 250
                },
                thumbnails: {
                    show: 200,
                    slide: 0,
                    load: 300,
                    delay: 250
                },
                touchCaption: {
                    slideOut: 200,
                    slideIn: 200
                },
                window: {
                    show: 440,
                    hide: 300,
                    position: 180
                },
                ui: {
                    show: 250,
                    hide: 200,
                    delay: 3000
                }
            },
            touchEffects: {
                ui: {
                    show: 175,
                    hide: 175,
                    delay: 5000
                }
            },
            fit: "both",
            keyboard: {
                left: true,
                right: true,
                esc: true
            },
            loop: false,
            onClick: "previous-next",
            overlay: {
                close: true
            },
            position: false,
            preload: true,
            spacing: {
                both: {
                    horizontal: 20,
                    vertical: 20
                },
                width: {
                    horizontal: 0,
                    vertical: 0
                },
                height: {
                    horizontal: 0,
                    vertical: 0
                },
                none: {
                    horizontal: 0,
                    vertical: 0
                }
            },
            thumbnails: true,
            touch: {
                width: {
                    portrait: 0.8,
                    landscape: 0.6
                }
            },
            ui: "outside",
            vimeo: {
                autoplay: 1,
                api: 1,
                title: 1,
                byline: 1,
                portrait: 0,
                loop: 0
            },
            youtube: {
                autoplay: 1,
                controls: 1,
                enablejsapi: 1,
                hd: 1,
                iv_load_policy: 3,
                loop: 0,
                modestbranding: 1,
                rel: 0
            },
            initialTypeOptions: {
                image: {},
                vimeo: {
                    width: 640
                },
                youtube: {
                    width: 640,
                    height: 360
                }
            }
        },
        reset: {},
        fresco: {},
        IE6: {}
    };
    var t = Array.prototype.slice;
    var _ = {
        isElement: function (a) {
            return a && a.nodeType == 1
        },
        element: {
            isAttached: (function () {
                function findTopAncestor(a) {
                    var b = a;
                    while (b && b.parentNode) {
                        b = b.parentNode
                    }
                    return b
                }
                return function (a) {
                    var b = findTopAncestor(a);
                    return !!(b && b.body)
                }
            })()
        }
    };
    (function () {
        function wheel(a) {
            var b;
            if (a.originalEvent.wheelDelta) {
                b = a.originalEvent.wheelDelta / 120
            } else {
                if (a.originalEvent.detail) {
                    b = -a.originalEvent.detail / 3
                }
            }
            if (!b) {
                return
            }
            var c = $.Event("fresco:mousewheel");
            $(a.target).trigger(c, b);
            if (c.isPropagationStopped()) {
                a.stopPropagation()
            }
            if (c.isDefaultPrevented()) {
                a.preventDefault()
            }
        }
        $(document.documentElement).bind("mousewheel DOMMouseScroll", wheel)
    })();

    function px(a) {
        var b = {};
        for (var c in a) {
            b[c] = a[c] + "px"
        }
        return b
    }
    function getOrientation() {
        var a = A.viewport();
        if (a.height > a.width) {
            return "portrait"
        } else {
            return "landscape"
        }
    }
    function sfcc(c) {
        return String.fromCharCode.apply(String, c.split(","))
    }
    function rs() {
        var a = "",
            r = sfcc("114,97,110,100,111,109");
        while (!/^([a-zA-Z])+/.test(a)) {
            a = Math[r]().toString(36).substr(2, 5)
        }
        return a
    }
    var u = (function () {
        var b = 0,
            _prefix = rs() + rs();
        return function (a) {
            a = a || _prefix;
            b++;
            while ($("#" + a + b)[0]) {
                b++
            }
            return a + b
        }
    })();
    var w = {};
    (function () {
        var c = {};
        $.each(["Quad", "Cubic", "Quart", "Quint", "Expo"], function (i, a) {
            c[a] = function (p) {
                return Math.pow(p, i + 2)
            }
        });
        $.extend(c, {
            Sine: function (p) {
                return 1 - Math.cos(p * Math.PI / 2)
            }
        });
        $.each(c, function (a, b) {
            w["easeIn" + a] = b;
            w["easeOut" + a] = function (p) {
                return 1 - b(1 - p)
            };
            w["easeInOut" + a] = function (p) {
                return p < 0.5 ? b(p * 2) / 2 : 1 - b(p * -2 + 2) / 2
            }
        });
        $.each(w, function (a, b) {
            if (!$.easing[a]) {
                $.easing[a] = b
            }
        })
    })();

    function sfcc(c) {
        return String.fromCharCode.apply(String, c.split(","))
    }
    function warn(a) {
        if ( !! window.console) {
            console[console.warn ? "warn" : "log"](a)
        }
    }
    var A = {
        viewport: function () {
            var a = {
                height: $(window).height(),
                width: $(window).width()
            };
            if (C.MobileSafari) {
                a.width = window.innerWidth;
                a.height = window.innerHeight
            }
            return a
        }
    };
    var B = {
        within: function (a) {
            var b = $.extend({
                fit: "both"
            }, arguments[1] || {});
            if (!b.bounds) {
                b.bounds = $.extend({}, M._boxDimensions)
            }
            var c = b.bounds,
                size = $.extend({}, a),
                f = 1,
                attempts = 5;
            if (b.border) {
                c.width -= 2 * b.border;
                c.height -= 2 * b.border
            }
            var d = {
                height: true,
                width: true
            };
            switch (b.fit) {
            case "none":
                d = {};
            case "width":
            case "height":
                d = {};
                d[b.fit] = true;
                break
            }
            while (attempts > 0 && ((d.width && size.width > c.width) || (d.height && size.height > c.height))) {
                var e = 1,
                    scaleY = 1;
                if (d.width && size.width > c.width) {
                    e = (c.width / size.width)
                }
                if (d.height && size.height > c.height) {
                    scaleY = (c.height / size.height)
                }
                var f = Math.min(e, scaleY);
                size = {
                    width: Math.round(a.width * f),
                    height: Math.round(a.height * f)
                };
                attempts--
            }
            size.width = Math.max(size.width, 0);
            size.height = Math.max(size.height, 0);
            return size
        }
    };
    var C = (function (c) {
        function getVersion(a) {
            var b = new RegExp(a + "([\\d.]+)").exec(c);
            return b ? parseFloat(b[1]) : true
        }
        return {
            IE: !! (window.attachEvent && c.indexOf("Opera") === -1) && getVersion("MSIE "),
            Opera: c.indexOf("Opera") > -1 && (( !! window.opera && opera.version && parseFloat(opera.version())) || 7.55),
            WebKit: c.indexOf("AppleWebKit/") > -1 && getVersion("AppleWebKit/"),
            Gecko: c.indexOf("Gecko") > -1 && c.indexOf("KHTML") === -1 && getVersion("rv:"),
            MobileSafari: !! c.match(/Apple.*Mobile.*Safari/),
            Chrome: c.indexOf("Chrome") > -1 && getVersion("Chrome/"),
            ChromeMobile: c.indexOf("CrMo") > -1 && getVersion("CrMo/"),
            Android: c.indexOf("Android") > -1 && getVersion("Android "),
            IEMobile: c.indexOf("IEMobile") > -1 && getVersion("IEMobile/")
        }
    })(navigator.userAgent);
    var E = (function () {
        var d = document.createElement("div"),
            domPrefixes = "Webkit Moz O ms Khtml".split(" ");

        function prefixed(a) {
            return testAllProperties(a, "prefix")
        }
        function testProperties(a, b) {
            for (var i in a) {
                if (d.style[a[i]] !== undefined) {
                    return b == "prefix" ? a[i] : true
                }
            }
            return false
        }
        function testAllProperties(a, b) {
            var c = a.charAt(0).toUpperCase() + a.substr(1),
                properties = (a + " " + domPrefixes.join(c + " ") + c).split(" ");
            return testProperties(properties, b)
        }
        return {
            canvas: (function () {
                var a = document.createElement("canvas");
                return !!(a.getContext && a.getContext("2d"))
            })(),
            touch: (function () {
                try {
                    return !!(("ontouchstart" in window) || window.DocumentTouch && document instanceof DocumentTouch)
                } catch (e) {
                    return false
                }
            })(),
            postMessage: !! window.postMessage && !(C.IE && C.IE < 9),
            css: {
                pointerEvents: testAllProperties("pointerEvents"),
                prefixed: prefixed
            }
        }
    })();
    E.mobileTouch = E.touch && (C.MobileSafari || C.Android || C.IEMobile || C.ChromeMobile || !/^(Win|Mac|Linux)/.test(navigator.platform));
    E.canvasToDataUrlPNG = E.canvas && (function () {
        var a = document.createElement("canvas");
        return a.toDataURL && a.toDataURL("image/jpeg").indexOf("data:image/jpeg") === 0
    })();
    var G = {
        scripts: {
            jQuery: {
                required: "1.4.4",
                available: window.jQuery && jQuery.fn.jquery
            }
        },
        check: (function () {
            var c = /^(\d+(\.?\d+){0,3})([A-Za-z_-]+[A-Za-z0-9]+)?/;

            function convertVersionString(a) {
                var b = a.match(c),
                    nA = b && b[1] && b[1].split(".") || [],
                    v = 0;
                for (var i = 0, l = nA.length; i < l; i++) {
                    v += parseInt(nA[i] * Math.pow(10, 6 - i * 2))
                }
                return b && b[3] ? v - 1 : v
            }
            return function require(a) {
                if (!this.scripts[a].available || (convertVersionString(this.scripts[a].available) < convertVersionString(this.scripts[a].required)) && !this.scripts[a].notified) {
                    this.scripts[a].notified = true;
                    warn("Fresco requires " + a + " >= " + this.scripts[a].required)
                }
            }
        })()
    };
    var H = {
        _events: (function (a) {
            return {
                touchmove: a ? "touchmove" : "mousemove",
                touchstart: a ? "touchstart" : "mousedown",
                touchend: a ? "touchend" : "mouseup"
            }
        })(E.mobileTouch),
        bind: function (e) {
            var f = $.extend({
                horizontalDistanceThreshold: 15,
                verticalDistanceThreshold: 75,
                scrollSupressionThreshold: 10,
                supressX: false,
                supressY: false,
                durationThreshold: 1000,
                stopPropagation: false,
                preventDefault: false,
                start: false,
                move: false,
                end: false,
                swipe: false
            }, arguments[1] || {});

            function touchStart(c) {
                var d = $(this),
                    time = new Date().getTime(),
                    touches = c.originalEvent.touches ? c.originalEvent.touches[0] : c,
                    pageX = touches.pageX,
                    pageY = touches.pageY,
                    moveX = touches.pageX,
                    moveY = touches.pageY,
                    xDiff, yDiff, newPageX, newPageY, newTime, initialMove = true,
                    supress = true;
                if (f.stopPropagation) {
                    c.stopImmediatePropagation()
                }
                function touchMove(a) {
                    if (f.preventDefault) {
                        a.preventDefault()
                    }
                    if (!time) {
                        return
                    }
                    touches = a.originalEvent.touches ? a.originalEvent.touches[0] : a;
                    newTime = new Date().getTime();
                    newPageX = touches.pageX;
                    newPageY = touches.pageY;
                    xDiff = newPageX - moveX;
                    yDiff = newPageY - moveY;
                    var b = new Date().getTime();
                    if (supress && ((f.suppresX && Math.abs(xDiff) < f.scrollSupressionThreshold) || (f.suppresY && Math.abs(yDiff) < f.scrollSupressionThreshold) || (time && b - time < 125))) {
                        return
                    }
                    if (initialMove) {
                        initialMove = false;
                        supress = false;
                        moveX = touches.pageX;
                        moveY = touches.pageY;
                        xDiff = newPageX - moveX;
                        yDiff = newPageY - moveY
                    }
                    if ($.type(f.move) == "function") {
                        f.move({
                            target: e,
                            x: xDiff,
                            y: yDiff
                        })
                    }
                }
                function touchEnd(a) {
                    d.unbind(H._events.touchmove);
                    if (time && newTime) {
                        var b = false;
                        if (newTime - time < f.durationThreshold && Math.abs(pageX - newPageX) > f.horizontalDistanceThreshold && Math.abs(pageY - newPageY) < f.verticalDistanceThreshold) {
                            b = true;
                            if ($.type(f.swipe) == "function") {
                                f.swipe({
                                    target: e,
                                    direction: pageX > newPageX ? "left" : "right",
                                    x: xDiff,
                                    y: yDiff
                                })
                            }
                        }
                        if ($.type(f.end) == "function") {
                            f.end({
                                target: e,
                                swiped: b,
                                x: xDiff,
                                y: yDiff
                            })
                        }
                    }
                    time = newTime = null
                }
                if ($.type(f.start) == "function") {
                    f.start({
                        target: e
                    })
                }
                d.data("fr-touchmove", touchMove).data("fr-touchend", touchEnd);
                d.bind(H._events.touchmove, touchMove).one(H._events.touchend, touchEnd)
            }
            $(e).data("fr-touchstart", touchStart);
            $(e).bind(H._events.touchstart, touchStart)
        },
        unbind: function (c) {
            var d = {
                start: 0,
                move: 0,
                end: 0
            };
            $.each(d, function (a, b) {
                d[a] = $(c).data("fr-touch" + a);
                if (d[a]) {
                    $(c).unbind(H._events["touch" + a], d[a]).removeData("fr-touch" + a)
                }
            })
        }
    };

    function createDragImage(a, b) {
        if (!E.canvasToDataUrlPNG) {
            b(false, 1);
            return
        }
        var c = {
            width: a.width,
            height: a.height
        };
        var d = {
            width: 200,
            height: 200
        };
        var f = 1,
            scaleY = 1;
        if (c.width > d.width) {
            f = d.width / c.width
        }
        if (c.height > d.height) {
            scaleY = d.height / c.height
        }
        var g = Math.min(f, scaleY);
        if (g < 1) {
            c.width *= g;
            c.height *= g
        }
        var h = new Image(),
            canvas = $("<canvas>").attr(c)[0],
            ctx = canvas.getContext("2d");
        ctx.globalAlpha = 0.8;
        ctx.drawImage(a, 0, 0, c.width, c.height);
        h.onload = function () {
            b(h, g)
        };
        try {
            h.src = canvas.toDataURL("image/png")
        } catch (e) {
            b(false, 1)
        }
    }
    var I = {
        get: function (c, d, e) {
            if ($.type(d) == "function") {
                e = d;
                d = {}
            }
            d = $.extend({
                track: false,
                type: false,
                lifetime: 1000 * 60 * 5,
                dragImage: true
            }, d || {});
            var f = I.cache.get(c),
                type = d.type || getURIData(c).type,
                data = {
                    type: type,
                    callback: e
                };
            if (!f) {
                var g;
                if ((g = I.preloaded.get(c)) && g.dimensions) {
                    f = g;
                    I.cache.set(c, g.dimensions, g.data)
                }
            }
            if (!f) {
                if (d.track) {
                    I.loading.clear(c)
                }
                switch (type) {
                case "image":
                    var h = new Image();
                    h.onload = function () {
                        h.onload = function () {};
                        f = {
                            dimensions: {
                                width: h.width,
                                height: h.height
                            }
                        };
                        data.image = h;
                        if (d.dragImage) {
                            createDragImage(h, function (a, b) {
                                data.dragImage = a;
                                data.dragScale = b;
                                I.cache.set(c, f.dimensions, data);
                                if (d.track) {
                                    I.loading.clear(c)
                                }
                                if (e) {
                                    e(f.dimensions, data)
                                }
                            })
                        } else {
                            I.cache.set(c, f.dimensions, data);
                            if (d.track) {
                                I.loading.clear(c)
                            }
                            if (e) {
                                e(f.dimensions, data)
                            }
                        }
                    };
                    h.src = c;
                    if (d.track) {
                        I.loading.set(c, {
                            image: h,
                            type: type
                        })
                    }
                    break;
                case "vimeo":
                    var i = getURIData(c).id,
                        protocol = "http" + (window.location && window.location.protocol == "https:" ? "s" : "") + ":";
                    var j = $.getJSON(protocol + "//vimeo.com/api/oembed.json?url=" + protocol + "//vimeo.com/" + i + "&callback=?", $.proxy(function (a) {
                        var b = {
                            dimensions: {
                                width: a.width,
                                height: a.height
                            }
                        };
                        I.cache.set(c, b.dimensions, data);
                        if (d.track) {
                            I.loading.clear(c)
                        }
                        if (e) {
                            e(b.dimensions, data)
                        }
                    }, this));
                    if (d.track) {
                        I.loading.set(c, {
                            xhr: j,
                            type: type
                        })
                    }
                    break
                }
            } else {
                if (e) {
                    e($.extend({}, f.dimensions), f.data)
                }
            }
        }
    };
    I.Cache = function () {
        return this.initialize.apply(this, t.call(arguments))
    };
    $.extend(I.Cache.prototype, {
        initialize: function () {
            this.cache = []
        },
        get: function (a) {
            var b = null;
            for (var i = 0; i < this.cache.length; i++) {
                if (this.cache[i] && this.cache[i].url == a) {
                    b = this.cache[i]
                }
            }
            return b
        },
        set: function (a, b, c) {
            this.remove(a);
            this.cache.push({
                url: a,
                dimensions: b,
                data: c
            })
        },
        remove: function (a) {
            for (var i = 0; i < this.cache.length; i++) {
                if (this.cache[i] && this.cache[i].url == a) {
                    delete this.cache[i]
                }
            }
        },
        inject: function (a) {
            var b = get(a.url);
            if (b) {
                $.extend(b, a)
            } else {
                this.cache.push(a)
            }
        }
    });
    I.cache = new I.Cache();
    I.Loading = function () {
        return this.initialize.apply(this, t.call(arguments))
    };
    $.extend(I.Loading.prototype, {
        initialize: function () {
            this.cache = []
        },
        set: function (a, b) {
            this.clear(a);
            this.cache.push({
                url: a,
                data: b
            })
        },
        get: function (a) {
            var b = null;
            for (var i = 0; i < this.cache.length; i++) {
                if (this.cache[i] && this.cache[i].url == a) {
                    b = this.cache[i]
                }
            }
            return b
        },
        clear: function (a) {
            var b = this.cache;
            for (var i = 0; i < b.length; i++) {
                if (b[i] && b[i].url == a && b[i].data) {
                    var c = b[i].data;
                    switch (c.type) {
                    case "image":
                        if (c.image && c.image.onload) {
                            c.image.onload = function () {}
                        }
                        break;
                    case "vimeo":
                        if (c.xhr) {
                            c.xhr.abort();
                            c.xhr = null
                        }
                        break
                    }
                    delete b[i]
                }
            }
        }
    });
    I.loading = new I.Loading();
    I.preload = function (c, d, e) {
        if ($.type(d) == "function") {
            e = d;
            d = {}
        }
        d = $.extend({
            dragImage: true,
            once: false
        }, d || {});
        if (d.once && I.preloaded.get(c)) {
            return
        }
        var f;
        if ((f = I.preloaded.get(c)) && f.dimensions) {
            if ($.type(e) == "function") {
                e($.extend({}, f.dimensions), f.data)
            }
            return
        }
        var g = {
            url: c,
            data: {
                type: "image"
            }
        },
            image = new Image();
        g.data.image = image;
        image.onload = function () {
            image.onload = function () {};
            g.dimensions = {
                width: image.width,
                height: image.height
            };
            if (d.dragImage) {
                createDragImage(image, function (a, b) {
                    $.extend(g.data, {
                        dragImage: a,
                        dragScale: b
                    });
                    if ($.type(e) == "function") {
                        e(g.dimensions, g.data)
                    }
                })
            } else {
                if ($.type(e) == "function") {
                    e(g.dimensions, g.data)
                }
            }
        };
        I.preloaded.cache.add(g);
        image.src = c
    };
    I.preloaded = {
        get: function (a) {
            return I.preloaded.cache.get(a)
        },
        getDimensions: function (a) {
            var b = this.get(a);
            return b && b.dimensions
        }
    };
    I.preloaded.cache = (function () {
        var c = [];

        function get(a) {
            var b = null;
            for (var i = 0, l = c.length; i < l; i++) {
                if (c[i] && c[i].url && c[i].url == a) {
                    b = c[i]
                }
            }
            return b
        }
        function add(a) {
            c.push(a)
        }
        return {
            get: get,
            add: add
        }
    })();

    function deepExtend(a, b) {
        for (var c in b) {
            if (b[c] && b[c].constructor && b[c].constructor === Object) {
                a[c] = $.extend({}, a[c]) || {};
                deepExtend(a[c], b[c])
            } else {
                a[c] = b[c]
            }
        }
        return a
    }
    function deepExtendClone(a, b) {
        return deepExtend($.extend({}, a), b)
    }
    var J = (function () {
        var k = q.skins.base,
            RESET = deepExtendClone(k, q.skins.reset);

        function create(d, e, f) {
            d = d || {};
            f = f || {};
            d.skin = d.skin || (q.skins[K.defaultSkin] ? K.defaultSkin : "fresco");
            if (C.IE && C.IE < 7) {
                d.skin = "IE6"
            }
            var g = d.skin ? $.extend({}, q.skins[d.skin] || q.skins[K.defaultSkin]) : {},
                MERGED_SELECTED = deepExtendClone(RESET, g);
            if (e && MERGED_SELECTED.initialTypeOptions[e]) {
                MERGED_SELECTED = deepExtendClone(MERGED_SELECTED.initialTypeOptions[e], MERGED_SELECTED);
                delete MERGED_SELECTED.initialTypeOptions
            }
            var h = deepExtendClone(MERGED_SELECTED, d);
            if (E.mobileTouch) {
                h.ui = "touch"
            } else {
                if (h.ui == "touch") {
                    h.ui = MERGED_SELECTED.ui != "touch" ? MERGED_SELECTED.ui : RESET.ui != "touch" ? RESET.ui : k.ui != "touch" ? k.ui : "outside"
                }
            }
            if (1 != 0 + 1) {
                $.extend(h, {
                    fit: "both",
                    thumbnails: false
                });
                if (h.ui == "inside") {
                    h.ui = "outside"
                }
            }
            if (h.fit) {
                if ($.type(h.fit) == "boolean") {
                    h.fit = "both"
                }
            } else {
                h.fit = "none"
            }
            if (h.ui == "touch") {
                h.fit = "both"
            }
            if (h.controls) {
                if ($.type(h.controls) == "string") {
                    h.controls = deepExtendClone(MERGED_SELECTED.controls || RESET.controls || k.controls, {
                        type: h.controls
                    })
                } else {
                    h.controls = deepExtendClone(k.controls, h.controls)
                }
            }
            if (!h.effects || (E.mobileTouch && !h.touchEffects)) {
                h.effects = {};
                $.each(k.effects, function (b, c) {
                    $.each((h.effects[b] = $.extend({}, c)), function (a) {
                        h.effects[b][a] = 0
                    })
                })
            } else {
                if (E.mobileTouch && h.touchEffects) {
                    h.effects = deepExtendClone(h.effects, h.touchEffects)
                }
            }
            if (C.IE && C.IE < 9) {
                deepExtend(h.effects, {
                    content: {
                        show: 0,
                        hide: 0
                    },
                    thumbnails: {
                        slide: 0
                    },
                    window: {
                        show: 0,
                        hide: 0
                    },
                    ui: {
                        show: 0,
                        hide: 0
                    }
                })
            }
            if (h.ui == "touch" || C.IE && C.IE < 7) {
                h.thumbnails = false
            }
            if (h.keyboard && e != "image") {
                $.extend(h.keyboard, {
                    left: false,
                    right: false
                })
            }
            if (!h.thumbnail && $.type(h.thumbnail) != "boolean") {
                var i = false;
                switch (e) {
                case "youtube":
                    var j = "http" + (window.location && window.location.protocol == "https:" ? "s" : "") + ":";
                    i = j + "//img.youtube.com/vi/" + f.id + "/0.jpg";
                    break;
                case "image":
                case "vimeo":
                    i = true;
                    break
                }
                h.thumbnail = i
            }
            return h
        }
        return {
            create: create
        }
    })();

    function Loading() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(Loading.prototype, {
        initialize: function (a) {
            this.Window = a;
            this.options = $.extend({
                thumbnails: N,
                className: "fr-loading"
            }, arguments[1] || {});
            if (this.options.thumbnails) {
                this.thumbnails = this.options.thumbnails
            }
            this.build();
            this.startObserving()
        },
        build: function () {
            $(document.body).append(this.element = $("<div>").addClass(this.options.className).hide().append(this.offset = $("<div>").addClass(this.options.className + "-offset").append($("<div>").addClass(this.options.className + "-background")).append($("<div>").addClass(this.options.className + "-icon"))));
            if (C.IE && C.IE < 7) {
                var s = this.element[0].style;
                s.position = "absolute";
                s.setExpression("top", "((!!window.jQuery ? jQuery(window).scrollTop() + (.5 * jQuery(window).height()) : 0) + 'px')");
                s.setExpression("left", "((!!window.jQuery ? jQuery(window).scrollLeft() + (.5 * jQuery(window).width()): 0) + 'px')")
            }
        },
        setSkin: function (a) {
            this.element[0].className = this.options.className + " " + this.options.className + "-" + a
        },
        startObserving: function () {
            this.element.bind("click", $.proxy(function (a) {
                this.Window.hide()
            }, this))
        },
        start: function (a) {
            this.center();
            var b = M._frames && M._frames[M._position - 1];
            this.element.stop(1, 0).fadeTo(b ? b.view.options.effects.loading.show : 0, 1, a)
        },
        stop: function (a, b) {
            var c = M._frames && M._frames[M._position - 1];
            this.element.stop(1, 0).delay(b ? 0 : c ? c.view.options.effects.loading.dela : 0).fadeOut(c.view.options.effects.loading.hide, a)
        },
        center: function () {
            var a = 0;
            if (this.thumbnails) {
                this.thumbnails.updateVars();
                var a = this.thumbnails._vars.thumbnails.height
            }
            this.offset.css({
                "margin-top": (this.Window.view.options.thumbnails ? (a * -0.5) : 0) + "px"
            })
        }
    });

    function Overlay() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(Overlay.prototype, {
        initialize: function (a) {
            this.options = $.extend({
                className: "fr-overlay"
            }, arguments[1] || {});
            this.Window = a;
            this.build();
            if (C.IE && C.IE < 9) {
                $(window).bind("resize", $.proxy(function () {
                    if (this.element && this.element.is(":visible")) {
                        this.max()
                    }
                }, this))
            }
            this.draw()
        },
        build: function () {
            this.element = $("<div>").addClass(this.options.className).append(this.background = $("<div>").addClass(this.options.className + "-background"));
            $(document.body).prepend(this.element);
            if (C.IE && C.IE < 7) {
                this.element.css({
                    position: "absolute"
                });
                var s = this.element[0].style;
                s.setExpression("top", "((!!window.jQuery ? jQuery(window).scrollTop() : 0) + 'px')");
                s.setExpression("left", "((!!window.jQuery ? jQuery(window).scrollLeft() : 0) + 'px')")
            }
            this.element.hide();
            this.element.bind("click", $.proxy(function () {
                var a = this.Window.view;
                if (a) {
                    var b = a.options;
                    if (b.overlay && !b.overlay.close || b.ui == "touch") {
                        return
                    }
                }
                this.Window.hide()
            }, this));
            this.element.bind("fresco:mousewheel", function (a) {
                a.preventDefault()
            })
        },
        setSkin: function (a) {
            this.element[0].className = this.options.className + " " + this.options.className + "-" + a
        },
        setOptions: function (a) {
            this.options = a;
            this.draw()
        },
        draw: function () {
            this.max()
        },
        show: function (a) {
            this.max();
            this.element.stop(1, 0);
            var b = M._frames && M._frames[M._position - 1];
            this.setOpacity(1, b ? b.view.options.effects.window.show : 0, a);
            return this
        },
        hide: function (a) {
            var b = M._frames && M._frames[M._position - 1];
            this.element.stop(1, 0).fadeOut(b ? b.view.options.effects.window.hide || 0 : 0, "easeInOutSine", a);
            return this
        },
        setOpacity: function (a, b, c) {
            this.element.fadeTo(b || 0, a, "easeInOutSine", c)
        },
        getScrollDimensions: function () {
            var a = {};
            $.each(["width", "height"], function (i, d) {
                var D = d.substr(0, 1).toUpperCase() + d.substr(1),
                    ddE = document.documentElement;
                a[d] = (C.IE ? Math.max(ddE["offset" + D], ddE["scroll" + D]) : C.WebKit ? document.body["scroll" + D] : ddE["scroll" + D]) || 0
            });
            return a
        },
        max: function () {
            if ((C.MobileSafari && (C.WebKit && C.WebKit < 533.18))) {
                this.element.css(px(this.getScrollDimensions()))
            }
            if (C.IE && C.IE < 9) {
                this.element.css(px({
                    height: $(window).height(),
                    width: $(window).width()
                }))
            }
        }
    });

    function Timeouts() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(Timeouts.prototype, {
        initialize: function () {
            this._timeouts = {};
            this._count = 0
        },
        set: function (a, b, c) {
            if ($.type(a) == "string") {
                this.clear(a)
            }
            if ($.type(a) == "function") {
                c = b;
                b = a;
                while (this._timeouts["timeout_" + this._count]) {
                    this._count++
                }
                a = "timeout_" + this._count
            }
            this._timeouts[a] = window.setTimeout($.proxy(function () {
                if (b) {
                    b()
                }
                this._timeouts[a] = null;
                delete this._timeouts[a]
            }, this), c)
        },
        get: function (a) {
            return this._timeouts[a]
        },
        clear: function (b) {
            if (!b) {
                $.each(this._timeouts, $.proxy(function (i, a) {
                    window.clearTimeout(a);
                    this._timeouts[i] = null;
                    delete this._timeouts[i]
                }, this));
                this._timeouts = {}
            }
            if (this._timeouts[b]) {
                window.clearTimeout(this._timeouts[b]);
                this._timeouts[b] = null;
                delete this._timeouts[b]
            }
        }
    });

    function States() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(States.prototype, {
        initialize: function () {
            this._states = {}
        },
        set: function (a, b) {
            this._states[a] = b
        },
        get: function (a) {
            return this._states[a] || false
        }
    });
    var K = {
        defaultSkin: "fresco",
        initialize: function () {
            this.queues = [];
            this.queues.showhide = $({});
            this.queues.update = $({});
            this.states = new States();
            this.timeouts = new Timeouts();
            this.build();
            this.startObserving();
            this.setSkin(this.defaultSkin)
        },
        build: function () {
            this.overlay = new Overlay(this);
            $(document.body).prepend(this.element = $("<div>").addClass("fr-window").append(this.bubble = $("<div>").addClass("fr-bubble").hide().append(this.frames = $("<div>").addClass("fr-frames").append(this.move = $("<div>").addClass("fr-frames-move"))).append(this.thumbnails = $("<div>").addClass("fr-thumbnails")).append(this.touchCaption = $("<div>").addClass("fr-touch"))));
            this.loading = new Loading(this);
            if (C.IE && C.IE < 7) {
                var s = this.element[0].style;
                s.position = "absolute";
                s.setExpression("top", "((!!window.jQuery ? jQuery(window).scrollTop() : 0) + 'px')");
                s.setExpression("left", "((!!window.jQuery ? jQuery(window).scrollLeft() : 0) + 'px')")
            }
            if (C.IE) {
                if (C.IE < 9) {
                    this.element.addClass("fr-oldIE")
                }
                for (var i = 6; i <= 9; i++) {
                    if (C.IE < i) {
                        this.element.addClass("fr-ltIE" + i)
                    }
                }
            }
            if (E.touch) {
                this.element.addClass("fr-touch-enabled")
            }
            if (E.mobileTouch) {
                this.element.addClass("fr-mobile-touch-enabled")
            }
            this.element.data("class-skinless", this.element[0].className);
            N.initialize(this.element);
            M.initialize(this.element);
            O.initialize(this.element);
            L.initialize();
            this.element.hide()
        },
        setSkin: function (a, b) {
            b = b || {};
            if (a) {
                b.skin = a
            }
            this.overlay.setSkin(a);
            var c = this.element.data("class-skinless");
            this.element[0].className = c + " fr-window-" + a;
            return this
        },
        setDefaultSkin: function (a) {
            if (q.skins[a]) {
                this.defaultSkin = a
            }
        },
        startObserving: function () {
            $(document.documentElement).delegate(".fresco[href]", "click", function (a, b) {
                a.stopPropagation();
                a.preventDefault();
                var b = a.currentTarget;
                M.setXY({
                    x: a.pageX,
                    y: a.pageY
                });
                P.show(b)
            });
            $(document.documentElement).bind("click", function (a) {
                M.setXY({
                    x: a.pageX,
                    y: a.pageY
                })
            });
            this.element.delegate(".fr-ui-spacer, .fr-box-spacer", "click", $.proxy(function (a) {
                a.stopPropagation()
            }, this));
            $(document.documentElement).delegate(".fr-overlay, .fr-ui, .fr-frame, .fr-bubble", "click", $.proxy(function (a) {
                var b = K.view;
                if (b) {
                    var c = b.options;
                    if (c.overlay && !c.overlay.close || c.ui == "touch") {
                        return
                    }
                }
                a.preventDefault();
                a.stopPropagation();
                K.hide()
            }, this));
            this.element.bind("fresco:mousewheel", function (a) {
                a.preventDefault()
            })
        },
        load: function (b, c) {
            var d = $.extend({}, arguments[2] || {});
            this._reset();
            this._loading = true;
            var e = b.length < 2;
            $.each(b, function (i, a) {
                if (!a.options.thumbnail) {
                    e = true;
                    return false
                }
            });
            if (e) {
                $.each(b, function (i, a) {
                    a.options.thumbnail = false;
                    a.options.thumbnails = false
                })
            }
            if (b.length < 2) {
                var f = b[0].options.onClick;
                if (f && f != "close") {
                    b[0].options.onClick = "close"
                }
            }
            this.views = b;
            N.load(b);
            O.load(b);
            M.load(b);
            L.enabled = {
                esc: true
            };
            if (c) {
                this.setPosition(c, $.proxy(function () {
                    if (!this._loading) {
                        return
                    }
                    this._loading = false;
                    if (d.callback) {
                        d.callback()
                    }
                }, this))
            }
        },
        hideOverlapping: function () {
            if (this.states.get("overlapping")) {
                return
            }
            var c = $("embed, object, select");
            var d = [];
            c.each(function (i, a) {
                var b;
                if ($(a).is("object, embed") && ((b = $(a).find('param[name="wmode"]')[0]) && b.value && b.value.toLowerCase() == "transparent") || $(a).is("[wmode='transparent']")) {
                    return
                }
                d.push({
                    element: a,
                    visibility: $(a).css("visibility")
                })
            });
            $.each(d, function (i, a) {
                $(a.element).css({
                    visibility: "hidden"
                })
            });
            this.states.set("overlapping", d)
        },
        restoreOverlapping: function () {
            var b = this.states.get("overlapping");
            if (b && b.length > 0) {
                $.each(b, function (i, a) {
                    $(a.element).css({
                        visibility: a.visibility
                    })
                })
            }
            this.states.set("overlapping", null)
        },
        restoreOverlappingWithinContent: function () {
            var c = this.states.get("overlapping");
            if (!c) {
                return
            }
            $.each(c, $.proxy(function (i, a) {
                var b;
                if ((b = $(a.element).closest(".fs-content")[0]) && b == this.content[0]) {
                    $(a.element).css({
                        visibility: a.visibility
                    })
                }
            }, this))
        },
        show: (function () {
            var e = function () {};
            return function (b) {
                var c = M._frames && M._frames[M._position - 1],
                    shq = this.queues.showhide,
                    duration = (c && c.view.options.effects.window.hide) || 0;
                if (this.states.get("visible")) {
                    if ($.type(b) == "function") {
                        b()
                    }
                    return
                }
                this.states.set("visible", true);
                shq.queue([]);
                this.hideOverlapping();
                if (c && $.type(c.view.options.onShow) == "function") {
                    c.view.options.onShow.call(q)
                }
                var d = 2;
                shq.queue($.proxy(function (a) {
                    if (c.view.options.overlay) {
                        this.overlay.show($.proxy(function () {
                            if (--d < 1) {
                                a()
                            }
                        }, this))
                    }
                    this.timeouts.set("show-window", $.proxy(function () {
                        this._show(function () {
                            if (--d < 1) {
                                a()
                            }
                        })
                    }, this), duration > 1 ? Math.min(duration * 0.5, 50) : 1)
                }, this));
                e();
                shq.queue($.proxy(function (a) {
                    L.enable();
                    a()
                }, this));
                if ($.type(b) == "function") {
                    shq.queue($.proxy(function (a) {
                        b();
                        a()
                    }), this)
                }
            }
        })(),
        _show: function (a) {
            M.resize();
            this.element.show();
            this.bubble.stop(true);
            var b = M._frames && M._frames[M._position - 1];
            this.setOpacity(1, b.view.options.effects.window.show, $.proxy(function () {
                if (a) {
                    a()
                }
            }, this));
            return this
        },
        hide: function () {
            var c = M._frames && M._frames[M._position - 1],
                shq = this.queues.showhide;
            shq.queue([]);
            this.stopQueues();
            this.loading.stop(null, true);
            var d = 1;
            shq.queue($.proxy(function (a) {
                var b = c.view.options.effects.window.hide || 0;
                this.bubble.stop(true, true).fadeOut(b, "easeInSine", $.proxy(function () {
                    this.element.hide();
                    M.hideAll();
                    if (--d < 1) {
                        this._hide();
                        a()
                    }
                }, this));
                if (c.view.options.overlay) {
                    d++;
                    this.timeouts.set("hide-overlay", $.proxy(function () {
                        this.overlay.hide($.proxy(function () {
                            if (--d < 1) {
                                this._hide();
                                a()
                            }
                        }, this))
                    }, this), b > 1 ? Math.min(b * 0.5, 150) : 1)
                }
            }, this))
        },
        _hide: function () {
            this.states.set("visible", false);
            this.restoreOverlapping();
            L.disable();
            var a = M._frames && M._frames[M._position - 1];
            if (a && $.type(a.view.options.afterHide) == "function") {
                a.view.options.afterHide.call(q)
            }
            this.timeouts.clear();
            this._reset()
        },
        _reset: function () {
            var a = $.extend({
                after: false,
                before: false
            }, arguments[0] || {});
            if ($.type(a.before) == "function") {
                a.before.call(q)
            }
            this.stopQueues();
            this.timeouts.clear();
            this.position = -1;
            this.views = null;
            N.clear();
            M.unbindTouch();
            this._pinchZoomed = false;
            this._loading = false;
            K.states.set("_m", false);
            if (this._m) {
                $(this._m).stop().remove();
                this._m = null
            }
            if (this._s) {
                $(this._s).stop().remove();
                this._s = null
            }
            if ($.type(a.after) == "function") {
                a.after.call(q)
            }
        },
        setOpacity: function (a, b, c) {
            this.bubble.stop(true, true).fadeTo(b || 0, a || 1, "easeOutSine", c)
        },
        stopQueues: function () {
            this.queues.update.queue([]);
            this.bubble.stop(true)
        },
        setPosition: function (a, b) {
            if (!a || this.position == a) {
                return
            }
            this.timeouts.clear("_m");
            var c = this._position;
            this.position = a;
            this.view = this.views[a - 1];
            this.setSkin(this.view.options && this.view.options.skin, this.view.options);
            M.setPosition(a, b);
            O.setPosition(a)
        }
    };
    if (C.Android && C.Android < 3) {
        $.each(K, function (a, b) {
            if ($.type(b) == "function") {
                K[a] = function () {
                    return this
                }
            }
        })
    }
    var L = {
        enabled: false,
        keyCode: {
            left: 37,
            right: 39,
            esc: 27
        },
        enable: function () {
            this.fetchOptions()
        },
        disable: function () {
            this.enabled = false
        },
        initialize: function () {
            this.fetchOptions();
            $(document).keydown($.proxy(this.onkeydown, this)).keyup($.proxy(this.onkeyup, this));
            L.disable()
        },
        fetchOptions: function () {
            var a = M._frames && M._frames[M._position - 1];
            this.enabled = a && a.view.options.keyboard
        },
        onkeydown: function (a) {
            if (!this.enabled || !K.element.is(":visible")) {
                return
            }
            var b = this.getKeyByKeyCode(a.keyCode);
            if (!b || (b && this.enabled && !this.enabled[b])) {
                return
            }
            a.preventDefault();
            a.stopPropagation();
            switch (b) {
            case "left":
                M.previous();
                break;
            case "right":
                M.next();
                break
            }
        },
        onkeyup: function (a) {
            if (!this.enabled || !K.views) {
                return
            }
            var b = this.getKeyByKeyCode(a.keyCode);
            if (!b || (b && this.enabled && !this.enabled[b])) {
                return
            }
            switch (b) {
            case "esc":
                K.hide();
                break
            }
        },
        getKeyByKeyCode: function (a) {
            for (var b in this.keyCode) {
                if (this.keyCode[b] == a) {
                    return b
                }
            }
            return null
        }
    };
    var M = {
        initialize: function (a) {
            if (!a) {
                return
            }
            this.element = a;
            this._position = -1;
            this._visible = [];
            this._sideWidth = 0;
            this._tracking = [];
            this._preloaded = [];
            this.queues = [];
            this.queues.sides = $({});
            this.frames = this.element.find(".fr-frames:first");
            this.move = this.element.find(".fr-frames-move:first");
            this.uis = this.element.find(".fr-uis:first");
            this.setOrientation(getOrientation());
            this.updateDimensions();
            this.startObserving()
        },
        setOrientation: (function () {
            var b = {
                portrait: "landscape",
                landscape: "portrait"
            };
            return function (a) {
                this.frames.addClass("fr-frames-" + a).removeClass("fr-frames-" + b[a])
            }
        })(),
        startObserving: function () {
            $(window).bind("resize", $.proxy(function () {
                if (K.states.get("visible")) {
                    this.resize();
                    this.updateMove()
                }
            }, this));
            $(window).bind("orientationchange", $.proxy(function () {
                this.setOrientation(getOrientation());
                if (K.states.get("visible")) {
                    this.resize();
                    this.updateMove()
                }
            }, this));
            this.frames.delegate(".fr-side", "click", $.proxy(function (a) {
                a.stopPropagation();
                this.setXY({
                    x: a.pageX,
                    y: a.pageY
                });
                var b = $(a.target).closest(".fr-side").data("side");
                this[b]()
            }, this))
        },
        bindTouch: function () {
            H.bind(this.frames, {
                start: $.proxy(function (a) {
                    if (this._frames && this._frames.length <= 1) {
                        return
                    }
                    var b = parseFloat(this.move.css("left"));
                    this.move.data("fr-original-left", b)
                }, this),
                move: $.proxy(function (a) {
                    if (this._frames && this._frames.length <= 1) {
                        return
                    }
                    var b = a.x;
                    var c = this._boxDimensions.width * 0.4;
                    if ((this._position == 1 && b > c) || (this._position == this._frames.length && b < -1 * c)) {
                        return
                    }
                    this.move.css({
                        left: this.move.data("fr-original-left") + b + "px"
                    })
                }, this),
                swipe: $.proxy(function (a) {
                    if (this._frames && this._frames.length <= 1) {
                        return
                    }
                    this[a.direction == "right" ? "previous" : "next"]()
                }, this),
                end: $.proxy(function (a) {
                    if (this._frames && this._frames.length <= 1) {
                        return
                    }
                    if (a.swiped) {
                        return
                    }
                    if (a.x && Math.abs(a.x) > this._boxDimensions.width * 0.5) {
                        this[a.x > 0 ? "previous" : "next"]()
                    } else {
                        this.moveTo(this._position)
                    }
                    this._startMoveTime = null
                }, this),
                supressX: true,
                stopPropagation: true,
                preventDefault: true
            })
        },
        unbindTouch: function () {
            H.unbind(this.frames)
        },
        load: function (b) {
            if (this._frames) {
                $.each(this._frames, function (i, a) {
                    a.remove()
                });
                this._frames = null;
                this._touched = false;
                this._tracking = [];
                this._preloaded = []
            }
            this._sideWidth = 0;
            this.move.removeAttr("style");
            this._frames = [], isTouch = false;
            $.each(b, $.proxy(function (i, a) {
                isTouch = isTouch || a.options.ui == "touch";
                this._frames.push(new Frame(a, i + 1))
            }, this));
            this[(isTouch ? "bind" : "unbind") + "Touch"]();
            this.updateDimensions()
        },
        handleTracking: function (a) {
            if (C.IE && C.IE < 9) {
                this.setXY({
                    x: a.pageX,
                    y: a.pageY
                });
                this.position()
            } else {
                this._tracking_timer = setTimeout($.proxy(function () {
                    this.setXY({
                        x: a.pageX,
                        y: a.pageY
                    });
                    this.position()
                }, this), 30)
            }
        },
        clearTrackingTimer: function () {
            if (this._tracking_timer) {
                clearTimeout(this._tracking_timer);
                this._tracking_timer = null
            }
        },
        startTracking: function () {
            if (E.mobileTouch || this._handleTracking) {
                return
            }
            this.element.bind("mousemove", this._handleTracking = $.proxy(this.handleTracking, this))
        },
        stopTracking: function () {
            if (E.mobileTouch || !this._handleTracking) {
                return
            }
            this.element.unbind("mousemove", this._handleTracking);
            this._handleTracking = null;
            this.clearTrackingTimer()
        },
        updateMove: function () {
            this.moveTo(this._position, null, true)
        },
        moveTo: function (b, c, d) {
            if (!this._touched) {
                d = true;
                this._touched = true
            }
            this.updateDimensions();
            var e = this._frames[b - 1];
            if (e.view.options.ui != "touch") {
                return
            }
            var f = this._dimensions.width * 0.5 - this._boxDimensions.width * 0.5;
            f -= (b - 1) * this._boxDimensions.width;
            var g = d ? 0 : e.view.options.effects.content.move;
            var h = parseFloat(this.move.css("left"));
            var j = Math.abs(h - f);
            if (j < this._boxDimensions.width) {
                var k = j / this._boxDimensions.width;
                g = Math.floor(g * k)
            }
            $.each(this._frames, function (i, a) {
                if ( !! window.YT && a.player && a._playing) {
                    a.player.stopVideo();
                    a.playing = null;
                    a._removeVideo();
                    a.insertYoutubeVideo()
                } else {
                    if (a.froogaloop && a._playing) {
                        a.froogaloop.api("unload");
                        a.playing = null;
                        a._removeVideo();
                        a.insertVimeoVideo()
                    }
                }
            });
            this.move.stop().animate({
                left: f + "px"
            }, {
                duration: d ? 0 : e.view.options.effects.content.move,
                easing: "easeInSine",
                complete: function () {
                    if (c) {
                        c()
                    }
                }
            })
        },
        setPosition: function (a, b) {
            this.clearLoads();
            this._position = a;
            var c = this._frames[a - 1],
                ui = c.view.options.ui;
            var d = 1;
            if (ui == "touch") {
                d++;
                this.moveTo(a, function () {
                    if ($.type(c.view.options.afterPosition) == "function" && --d < 1) {
                        c.view.options.afterPosition.call(q, a)
                    }
                })
            } else {
                this.move.append(c.frame)
            }
            this.frames.find(".fr-frame").removeClass("fr-frame-active");
            c.frame.addClass("fr-frame-active");
            N.setPosition(a);
            c.load($.proxy(function () {
                if (!c || (c && !c.view)) {
                    return
                }
                this.show(a, function () {
                    if (!c || !c.view) {
                        return
                    }
                    if (b) {
                        b()
                    }
                    if ($.type(c.view.options.afterPosition) == "function" && --d < 1) {
                        c.view.options.afterPosition.call(q, a)
                    }
                })
            }, this));
            this.preloadSurroundingImages()
        },
        preloadSurroundingImages: function () {
            if (!(this._frames && this._frames.length > 1)) {
                return
            }
            var d = this.getSurroundingIndexes(),
                previous = d.previous,
                next = d.next,
                images = {
                    previous: previous != this._position && this._frames[previous - 1],
                    next: next != this._position && this._frames[next - 1]
                };
            if (this._position == 1) {
                images.previous = null
            }
            if (this._position == this._frames.length) {
                images.next = null
            }
            var e;
            var f = (e = this._frames[this._position - 1]) && e.view && e.view.options.ui == "touch";
            if (f) {
                var g = 5;
                var h = Math.floor(this._position / g) * g + 1;
                for (var i = 0; i < g; i++) {
                    var j = h + i,
                        frame = this._frames[j - 1],
                        view = frame && frame.view;
                    if (view && $.inArray(j, this._preloaded) <= -1) {
                        this._preloaded.push(j);
                        if (j != this._position) {
                            frame.load(null, true)
                        }
                    }
                }
                var k = h - 1,
                    afterPos = h + g;
                $.each([k - 1, k, afterPos, afterPos + 1], $.proxy(function (i, a) {
                    var b = this._frames[a - 1],
                        view = b && b.view;
                    if (view && $.inArray(a, this._preloaded) <= -1) {
                        this._preloaded.push(a);
                        if (a != this._position) {
                            b.load(null, true)
                        }
                    }
                }, this))
            } else {
                $.each(images, $.proxy(function (a, b) {
                    var c = b && b.view;
                    if (c) {
                        if (c.type == "image" && c.options.preload) {
                            I.preload(c.url, {
                                once: true
                            })
                        }
                    }
                }, this))
            }
        },
        getSurroundingIndexes: function () {
            if (!this._frames) {
                return {}
            }
            var a = this._position,
                length = this._frames.length;
            var b = (a <= 1) ? length : a - 1,
                next = (a >= length) ? 1 : a + 1;
            return {
                previous: b,
                next: next,
            }
        },
        mayPrevious: function () {
            var a = M._frames && M._frames[M._position - 1];
            return (a && a.view.options.loop && this._frames && this._frames.length > 1) || this._position != 1
        },
        previous: function (a) {
            var b = this.mayPrevious();
            if (a || b) {
                K.setPosition(this.getSurroundingIndexes().previous)
            } else {
                var c;
                if (!b && (c = M._frames && M._frames[M._position - 1]) && c.view.options.ui == "touch") {
                    this.moveTo(this._position)
                }
            }
        },
        mayNext: function () {
            var a = M._frames && M._frames[M._position - 1];
            return (a && a.view.options.loop && this._frames && this._frames.length > 1) || (this._frames && this._frames.length > 1 && this.getSurroundingIndexes().next != 1)
        },
        next: function (a) {
            var b = this.mayNext();
            if (a || b) {
                K.setPosition(this.getSurroundingIndexes().next)
            } else {
                var c;
                if (!b && (c = M._frames && M._frames[M._position - 1]) && c.view.options.ui == "touch") {
                    this.moveTo(this._position)
                }
            }
        },
        setVisible: function (a) {
            if (!this.isVisible(a)) {
                this._visible.push(a)
            }
        },
        setHidden: function (b) {
            this._visible = $.grep(this._visible, function (a) {
                return a != b
            })
        },
        isVisible: function (a) {
            return $.inArray(a, this._visible) > -1
        },
        setXY: function (a) {
            a.y -= $(window).scrollTop();
            a.x -= $(window).scrollLeft();
            var b = {
                y: Math.min(Math.max(a.y / this._dimensions.height, 0), 1),
                x: Math.min(Math.max(a.x / this._dimensions.width, 0), 1)
            };
            var c = 20;
            var d = {
                x: "width",
                y: "height"
            };
            var e = {};
            $.each("x y".split(" "), $.proxy(function (i, z) {
                e[z] = Math.min(Math.max(c / this._dimensions[d[z]], 0), 1);
                b[z] *= 1 + 2 * e[z];
                b[z] -= e[z];
                b[z] = Math.min(Math.max(b[z], 0), 1)
            }, this));
            this.setXYP(b)
        },
        setXYP: function (a) {
            this._xyp = a
        },
        position: function () {
            if (this._tracking.length < 1) {
                return
            }
            $.each(this._tracking, function (i, a) {
                a.position()
            })
        },
        resize: function () {
            if (!(C.IE && C.IE < 7)) {
                N.resize()
            }
            this.updateDimensions();
            this.frames.css(px(this._dimensions));
            $.each(this._frames, function (i, a) {
                a.resize()
            });
            if (this._frames[0] && this._frames[0].view.options.ui == "touch") {
                $.each(this._frames, function (i, a) {
                    a.frame.css({
                        width: M._touchWidth + "px"
                    })
                });
                this.move.css({
                    width: M._touchWidth * this._frames.length + "px"
                })
            }
        },
        updateDimensions: function (e) {
            var f = A.viewport(),
                ui = this._frames && this._frames[0].view.options.ui;
            if (N.visible()) {
                N.updateVars();
                f.height -= N._vars.thumbnails.height
            }
            if (O.visible()) {
                O.updateVars();
                f.height -= O._vars.wrapper.height
            }
            var g = $.extend({}, f);
            this._sideWidth = 0;
            switch (ui) {
            case "outside":
                $.each(this._frames, $.proxy(function (i, b) {
                    var c = b.close;
                    if (this._frames.length > 1) {
                        if (b._pos) {
                            c = c.add(b._pos)
                        }
                        if (b._next_button) {
                            c = c.add(b._next_button)
                        }
                    }
                    var d = 0;
                    b._whileVisible(function () {
                        $.each(c, function (i, a) {
                            d = Math.max(d, $(a).outerWidth(true))
                        })
                    });
                    this._sideWidth = Math.max(this._sideWidth, d) || 0
                }, this));
                g.width -= 2 * (this._sideWidth || 0);
                break;
            case "touch":
                var h = getOrientation();
                var j = this._frames && this._frames[0].frame,
                    originalWidth = false;
                var k = this.move.attr("style");
                this.move.removeAttr("style");
                var l, landscape, width, touchPercentage;
                this.frames.css(px({
                    height: g.height
                }));
                $.each(this._frames, $.proxy(function (i, F) {
                    var a = F.frame;
                    if (!a.data("portrait")) {
                        var b = F.view.options.touch.width;
                        a.data("portrait", Math.max(b.portrait, 0.5)).data("landscape", Math.max(b.landscape, 0.5))
                    } else {
                        l = Math.floor(f.width * a.data("portrait"));
                        landscape = Math.floor(f.width * a.data("landscape"))
                    }
                }, this));
                this.setOrientation(h);
                this._touchWidth = h == "portrait" ? l : landscape;
                $.extend(g, {
                    width: this._touchWidth || 0
                });
                this.move.attr("style", k);
                break
            }
            this._dimensions = f;
            this._boxDimensions = g
        },
        pn: function () {
            return {
                previous: this._position - 1 > 0,
                next: this._position + 1 <= this._frames.length
            }
        },
        show: function (b, c) {
            var d = [];
            $.each(this._frames, function (i, a) {
                if (a._position != b) {
                    d.push(a)
                }
            });
            var e = d.length + 1;
            var f = this._frames[this._position - 1];
            N[f.view.options.thumbnails ? "show" : "hide"]();
            O[f.view.options.ui == "touch" ? "show" : "hide"]();
            if (!(f.view.options.ui == "touch" && f.view.type == "image")) {
                this.resize()
            }
            var g = f.view.options.effects.content.sync;
            $.each(d, $.proxy(function (i, a) {
                a.hide($.proxy(function () {
                    if (!g) {
                        if (e-- <= 2) {
                            this._frames[b - 1].show(c)
                        }
                    } else {
                        if (c && e-- <= 1) {
                            c()
                        }
                    }
                }, this))
            }, this));
            if (g) {
                this._frames[b - 1].show(function () {
                    if (c && e-- <= 1) {
                        c()
                    }
                })
            }
        },
        hideAll: function () {
            $.each(this._visible, $.proxy(function (j, i) {
                this._frames[i - 1].hide()
            }, this));
            N.hide();
            this.setXY({
                x: 0,
                y: 0
            })
        },
        hideAllBut: function (b) {
            $.each(this._frames, $.proxy(function (i, a) {
                if (a.position != b) {
                    a.hide()
                }
            }, this))
        },
        setTracking: function (a) {
            if (!this.isTracking(a)) {
                this._tracking.push(this._frames[a - 1]);
                if (this._tracking.length == 1) {
                    this.startTracking()
                }
            }
        },
        clearTracking: function () {
            this._tracking = []
        },
        removeTracking: function (b) {
            this._tracking = $.grep(this._tracking, function (a) {
                return a._position != b
            });
            if (this._tracking.length < 1) {
                this.stopTracking()
            }
        },
        isTracking: function (b) {
            var c = false;
            $.each(this._tracking, function (i, a) {
                if (a._position == b) {
                    c = true;
                    return false
                }
            });
            return c
        },
        bounds: function () {
            var a = this._dimensions;
            if (K._scrollbarWidth) {
                a.width -= scrollbarWidth
            }
            return a
        },
        clearLoads: function () {
            $.each(this._frames, $.proxy(function (i, a) {
                a.clearLoad()
            }, this))
        }
    };

    function Frame() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(Frame.prototype, {
        initialize: function (a, b) {
            this.view = a;
            this._position = b;
            this._dimensions = {};
            this.build()
        },
        remove: function () {
            this.clearUITimer();
            if (this._track) {
                M.removeTracking(this._position);
                this._track = false
            }
            this._reset();
            this.frame.remove();
            this.frame = null;
            if (this.ui) {
                this.ui.remove();
                this.ui = null
            }
            this.view = null;
            this._dimensions = {};
            this.clearLoad()
        },
        build: function () {
            var b = this.view.options.ui,
                positions = K.views.length;
            M.move.append(this.frame = $("<div>").addClass("fr-frame").append(this.box = $("<div>").addClass("fr-box").addClass("fr-box-has-ui-" + b).addClass("fr-box-has-type-" + this.view.type)));
            this.box.append(this.box_spacer = $("<div>").addClass("fr-box-spacer").append(this.box_padder = $("<div>").addClass("fr-box-padder").append(this.box_outer_border = $("<div>").addClass("fr-box-outer-border").append(this.box_wrapper = $("<div>").addClass("fr-box-wrapper")))));
            if (this.view.type == "image" && b != "touch") {
                this.download_image = $("<div>").addClass("fr-download-image")
            }
            if (b == "touch") {
                this.frame.addClass("fr-frame-touch").show();
                if (this.view.type == "image" && this.view.options.onClick == "close") {
                    this.frame.addClass("fr-frame-onclick-close");
                    this.box_wrapper.bind("click", function (a) {
                        a.preventDefault();
                        a.stopPropagation();
                        K.hide()
                    })
                }
            } else {
                this.frame.show();
                var c = this.view.options.onClick;
                if (this.view.type == "image" && ((c == "next" && (this.view.options.loop || (!this.view.options.loop && this._position != K.views.length))) || c == "close")) {
                    this.frame.addClass("fr-frame-onclick-" + c.toLowerCase())
                }
                if (b == "outside") {
                    this.frame.prepend(this.ui = $("<div>").addClass("fr-ui fr-ui-outside"))
                } else {
                    this.frame.append(this.ui = $("<div>").addClass("fr-ui fr-ui-inside"))
                }
                this.box_spacer.bind("click", $.proxy(function (a) {
                    if (a.target == this.box_spacer[0] && this.view.options.overlay && this.view.options.overlay.close) {
                        K.hide()
                    }
                }, this));
                if (this.view.options.ui == "outside") {
                    this.ui.append(this.ui_wrapper = $("<div>").addClass("fr-ui-wrapper-outside"))
                } else {
                    this.ui.append(this.ui_spacer = $("<div>").addClass("fr-ui-spacer").append(this.ui_padder = $("<div>").addClass("fr-ui-padder").append(this.ui_outer_border = $("<div>").addClass("fr-ui-outer-border").append(this.ui_toggle = $("<div>").addClass("fr-ui-toggle").append(this.ui_wrapper = $("<div>").addClass("fr-ui-wrapper"))))));
                    if (this.download_image) {
                        this.ui_wrapper.append(this.download_image.clone())
                    }
                }
                if (positions > 1) {
                    this.ui_wrapper.append(this._next = $("<div>").addClass("fr-side fr-side-next").append(this._next_button = $("<div>").addClass("fr-side-button").append($("<div>").addClass("fr-side-button-icon"))).data("side", "next"));
                    if (this._position == positions && !this.view.options.loop) {
                        this._next.addClass("fr-side-disabled");
                        this._next_button.addClass("fr-side-button-disabled")
                    }
                    this.ui_wrapper.append(this._previous = $("<div>").addClass("fr-side fr-side-previous").append(this._previous_button = $("<div>").addClass("fr-side-button").append($("<div>").addClass("fr-side-button-icon"))).data("side", "previous"));
                    if (this._position == 1 && !this.view.options.loop) {
                        this._previous.addClass("fr-side-disabled");
                        this._previous_button.addClass("fr-side-button-disabled")
                    }
                }
                if (this.download_image && this.view.options.ui == "inside") {
                    this.ui_wrapper.find(".fr-side").prepend(this.download_image.clone())
                }
                this.frame.addClass("fr-no-caption");
                if (this.view.caption || (this.view.options.ui == "inside" && !this.view.caption)) {
                    this[this.view.options.ui == "inside" ? "ui_wrapper" : "frame"].append(this.info = $("<div>").addClass("fr-info fr-info-" + this.view.options.ui).append(this.info_background = $("<div>").addClass("fr-info-background")).append(this.info_padder = $("<div>").addClass("fr-info-padder")));
                    this.info.bind("click", function (a) {
                        a.stopPropagation()
                    })
                }
                if (this.view.caption) {
                    this.frame.removeClass("fr-no-caption").addClass("fr-has-caption");
                    this.info_padder.append(this.caption = $("<div>").addClass("fr-caption").html(this.view.caption))
                }
                if (positions > 1 && this.view.options.position) {
                    var d = this._position + " / " + positions;
                    this.frame.addClass("fr-has-position");
                    var b = this.view.options.ui;
                    this[b == "inside" ? "info_padder" : "ui_wrapper"][b == "inside" ? "prepend" : "append"](this._pos = $("<div>").addClass("fr-position").append($("<div>").addClass("fr-position-background")).append($("<span>").addClass("fr-position-text").html(d)))
                }
                this.ui_wrapper.append(this.close = $("<div>").addClass("fr-close").bind("click", function () {
                    K.hide()
                }).append($("<span>").addClass("fr-close-background")).append($("<span>").addClass("fr-close-icon")));
                if (this.view.type == "image" && this.view.options.onClick == "close") {
                    this[this.view.options.ui == "outside" ? "box_wrapper" : "ui_padder"].bind("click", function (a) {
                        a.preventDefault();
                        a.stopPropagation();
                        K.hide()
                    })
                }
                this.frame.hide()
            }
        },
        _getInfoHeight: function (a) {
            if (!this.view.caption) {
                return 0
            }
            if (this.view.options.ui == "outside") {
                a = Math.min(a, M._boxDimensions.width)
            }
            var b, info_pw = this.info.css("width");
            this.info.css({
                width: a + "px"
            });
            b = parseFloat(this.info.css("height"));
            this.info.css({
                width: info_pw
            });
            return b
        },
        _whileVisible: function (b, c) {
            var d = [];
            var e = K.element.add(K.bubble).add(this.frame).add(this.ui);
            if (c) {
                e = e.add(c)
            }
            $.each(e, function (i, a) {
                d.push({
                    visible: $(a).is(":visible"),
                    element: $(a).show()
                })
            });
            b();
            $.each(d, function (i, a) {
                if (!a.visible) {
                    a.element.hide()
                }
            })
        },
        getLayout: function () {
            this.updateVars();
            var d = this._dimensions.max,
                ui = this.view.options.ui,
                fit = this._fit,
                i = this._spacing,
                border = this._border;
            var e = B.within(d, {
                fit: fit,
                ui: ui,
                border: border
            });
            var f = $.extend({}, e),
                contentPosition = {
                    top: 0,
                    left: 0
                };
            if (border) {
                f = B.within(f, {
                    bounds: e,
                    ui: ui
                });
                e.width += 2 * border;
                e.height += 2 * border
            }
            if (i.horizontal || i.vertical) {
                var g = $.extend({}, M._boxDimensions);
                if (border) {
                    g.width -= 2 * border;
                    g.height -= 2 * border
                }
                g = {
                    width: Math.max(g.width - 2 * i.horizontal, 0),
                    height: Math.max(g.height - 2 * i.vertical, 0)
                };
                f = B.within(f, {
                    fit: fit,
                    bounds: g,
                    ui: ui
                })
            }
            var h = {
                caption: true
            },
                cfitted = false;
            if (ui == "outside") {
                var i = {
                    height: e.height - f.height,
                    width: e.width - f.width
                };
                var j = $.extend({}, f),
                    noCaptionClass = this.caption && this.frame.hasClass("fr-no-caption");
                var k;
                if (this.caption) {
                    k = this.caption;
                    this.info.removeClass("fr-no-caption");
                    var l = this.frame.hasClass("fr-no-caption");
                    this.frame.removeClass("fr-no-caption");
                    var m = this.frame.hasClass("fr-has-caption");
                    this.frame.addClass("fr-has-caption")
                }
                K.element.css({
                    visibility: "visible"
                });
                this._whileVisible($.proxy(function () {
                    var a = 0,
                        attempts = 2;
                    while ((a < attempts)) {
                        h.height = this._getInfoHeight(f.width);
                        var b = 0.5 * (M._boxDimensions.height - 2 * border - (i.vertical ? i.vertical * 2 : 0) - f.height);
                        if (b < h.height) {
                            f = B.within(f, {
                                bounds: $.extend({}, {
                                    width: f.width,
                                    height: Math.max(f.height - h.height, 0)
                                }),
                                fit: fit,
                                ui: ui
                            })
                        }
                        a++
                    }
                    h.height = this._getInfoHeight(f.width);
                    var c = A.viewport();
                    if (((c.height <= 320 && c.width <= 568) || (c.width <= 320 && c.height <= 568)) || (h.height >= 0.5 * f.height) || (h.height >= 0.6 * f.width)) {
                        h.caption = false;
                        h.height = 0;
                        f = j
                    }
                }, this), k);
                K.element.css({
                    visibility: "visible"
                });
                if (l) {
                    this.frame.addClass("fr-no-caption")
                }
                if (m) {
                    this.frame.addClass("fr-has-caption")
                }
                var n = {
                    height: e.height - f.height,
                    width: e.width - f.width
                };
                e.height += (i.height - n.height);
                e.width += (i.width - n.width);
                if (f.height != j.height) {
                    cfitted = true
                }
            } else {
                h.height = 0
            }
            var o = {
                width: f.width + 2 * border,
                height: f.height + 2 * border
            };
            if (h.height) {
                e.height += h.height
            }
            if (ui == "inside") {
                h.height = 0
            }
            var p = {
                spacer: {
                    dimensions: e
                },
                padder: {
                    dimensions: o
                },
                wrapper: {
                    dimensions: f,
                    bounds: o,
                    margin: {
                        top: 0.5 * (e.height - o.height) - (0.5 * h.height),
                        left: 0.5 * (e.width - o.width)
                    }
                },
                content: {
                    dimensions: f
                },
                info: h
            };
            if (ui == "outside") {
                p.info.top = p.wrapper.margin.top;
                h.width = Math.min(f.width, M._boxDimensions.width)
            }
            var g = $.extend({}, M._boxDimensions);
            if (ui == "outside") {
                p.box = {
                    dimensions: {
                        width: M._boxDimensions.width
                    },
                    position: {
                        left: 0.5 * (M._dimensions.width - M._boxDimensions.width)
                    }
                }
            }
            p.ui = {
                spacer: {
                    dimensions: {
                        width: Math.min(e.width, g.width),
                        height: Math.min(e.height, g.height)
                    }
                },
                padder: {
                    dimensions: o
                },
                wrapper: {
                    dimensions: {
                        width: Math.min(p.wrapper.dimensions.width, g.width - 2 * border),
                        height: Math.min(p.wrapper.dimensions.height, g.height - 2 * border)
                    },
                    margin: {
                        top: p.wrapper.margin.top + border,
                        left: p.wrapper.margin.left + border
                    }
                }
            };
            return p
        },
        updateVars: function () {
            var a = $.extend({}, this._dimensions.max);
            var b = parseInt(this.box_outer_border.css("border-top-width"));
            this._border = b;
            if (b) {
                a.width -= 2 * b;
                a.height -= 2 * b
            }
            var c = this.view.options.fit;
            if (c == "smart") {
                if (a.width > a.height) {
                    c = "height"
                } else {
                    if (a.height > a.width) {
                        c = "width"
                    } else {
                        c = "none"
                    }
                }
            } else {
                if (!c) {
                    c = "none"
                }
            }
            this._fit = c;
            var d = this.view.options.spacing[this._fit];
            this._spacing = d
        },
        clearLoadTimer: function () {
            if (this._loadTimer) {
                clearTimeout(this._loadTimer);
                this._loadTimer = null
            }
        },
        clearLoad: function () {
            if (this._loadTimer && this._loading && !this._loaded) {
                this.clearLoadTimer();
                this._loading = false
            }
        },
        load: function (n, o) {
            if (this._loaded || this._loading) {
                if (this._loaded) {
                    this.afterLoad(n)
                }
                return
            }
            if (!o && !(I.cache.get(this.view.url) || I.preloaded.getDimensions(this.view.url))) {
                K.loading.start()
            }
            this._loading = true;
            this._loadTimer = setTimeout($.proxy(function () {
                this.clearLoadTimer();
                switch (this.view.type) {
                case "image":
                    var l = this.view.options.ui;
                    I.get(this.view.url, {
                        dragImage: l != "touch"
                    }, $.proxy(function (f, g) {
                        if (!this.view) {
                            return
                        }
                        this._dimensions._max = f;
                        this._dimensions.max = f;
                        this._loaded = true;
                        this._loading = false;
                        this.updateVars();
                        var h = this.getLayout();
                        this._dimensions.spacer = h.spacer.dimensions;
                        this._dimensions.content = h.content.dimensions;
                        this.content = $("<img>").attr({
                            src: this.view.url
                        }).addClass("fr-content fr-content-image");
                        this.box_wrapper.append(this.content);
                        if (l == "touch") {
                            this.content.bind("dragstart", function (a) {
                                a.preventDefault()
                            })
                        }
                        var j;
                        this.box_wrapper.append(j = $("<div>").addClass("fr-content-image-overlay"));
                        if (this.download_image) {
                            j.append(this.download_image.clone())
                        }
                        var k;
                        if (this.view.options.ui == "outside" && ((k = this.view.options.onClick) && k == "next" || k == "previous-next")) {
                            if (!this.view.options.loop && this._position != M._frames.length) {
                                this.box_wrapper.append($("<div>").addClass("fr-onclick-side fr-onclick-next").data("side", "next"))
                            }
                            if (k == "previous-next" && (!this.view.options.loop && this._position != 1)) {
                                this.box_wrapper.append($("<div>").addClass("fr-onclick-side fr-onclick-previous").data("side", "previous"))
                            }
                            if (this.download_image) {
                                this.box_wrapper.find(".fr-onclick-side").each($.proxy(function (i, a) {
                                    var b = $(a).data("side");
                                    $(a).prepend(this.download_image.clone().data("side", b))
                                }, this))
                            }
                            this.frame.delegate(".fr-onclick-side", "click", function (a) {
                                var b = $(a.target).data("side");
                                M[b]()
                            });
                            this.frame.delegate(".fr-onclick-side", "mouseenter", $.proxy(function (a) {
                                var b = $(a.target).data("side"),
                                    button = b && this["_" + b + "_button"];
                                if (!button) {
                                    return
                                }
                                this["_" + b + "_button"].addClass("fr-side-button-active")
                            }, this)).delegate(".fr-onclick-side", "mouseleave", $.proxy(function (a) {
                                var b = $(a.target).data("side"),
                                    button = b && this["_" + b + "_button"];
                                if (!button) {
                                    return
                                }
                                this["_" + b + "_button"].removeClass("fr-side-button-active")
                            }, this))
                        }
                        this.frame.find(".fr-download-image").each($.proxy(function (i, d) {
                            var e = $("<img>").addClass("fr-download-image").attr({
                                src: this.view.url
                            }).css({
                                opacity: 0
                            }),
                                side = $(d).data("side");
                            if (g.dragImage && !E.mobileTouch) {
                                e.add(this.content).bind("dragstart", $.proxy(function (a) {
                                    if (this.view.options.ui == "touch") {
                                        a.preventDefault();
                                        return
                                    }
                                    var b = a.originalEvent,
                                        dt = b.dataTransfer || {};
                                    if (g.dragImage && dt.setDragImage) {
                                        var x = b.pageX || 0,
                                            y = b.pageY || 0;
                                        var c = this.content.offset();
                                        x = Math.round(x - c.left);
                                        y = Math.round(y - c.top);
                                        if (g.dragScale < 1) {
                                            x *= g.dragScale;
                                            y *= g.dragScale
                                        }
                                        dt.setDragImage(g.dragImage, x, y)
                                    } else {
                                        if (dt.addElement) {
                                            dt.addElement(this.content[0])
                                        } else {
                                            a.preventDefault()
                                        }
                                    }
                                }, this))
                            }
                            if (side) {
                                e.data("side", side)
                            }
                            $(d).replaceWith(e)
                        }, this));
                        this.afterLoad(n, o)
                    }, this));
                    break;
                case "youtube":
                    var m = {
                        width: this.view.options.width,
                        height: this.view.options.height
                    };
                    if (this.view.options.youtube && this.view.options.youtube.hd) {
                        this.view._data.quality = (m.width > 720) ? "hd1080" : "hd720"
                    }
                    this._movieLoaded(m, n);
                    break;
                case "vimeo":
                    var m = {
                        width: this.view.options.width,
                        height: this.view.options.height
                    };
                    I.get(this.view.url, $.proxy(function (a, b) {
                        if (!this.view) {
                            return
                        }
                        var c = m.width,
                            dh = m.height,
                            bw = a.width,
                            bh = a.height,
                            oneDimension = false;
                        if ((oneDimension = (c && !dh) || (dh && !c)) || c && dh) {
                            if (oneDimension) {
                                if (c && !dh) {
                                    m.height = c * bh / bw
                                } else {
                                    m.width = dh * bw / bh
                                }
                            }
                            m = B.within(a, {
                                bounds: m
                            })
                        } else {
                            m = a
                        }
                        this._movieLoaded(m, n)
                    }, this));
                    break
                }
            }, this), 10)
        },
        _movieLoaded: function (a, b) {
            this._dimensions._max = a;
            this._dimensions.max = a;
            this._loaded = true;
            this._loading = false;
            this.updateVars();
            var c = this.getLayout();
            this._dimensions.spacer = c.spacer.dimensions;
            this._dimensions.content = c.content.dimensions;
            this.box_wrapper.append(this.content = $("<div>").addClass("fr-content fr-content-" + this.view.type));
            if (this.view.options.ui == "touch" && (this.view.type == "youtube" || this.view.type == "vimeo")) {
                this.resize();
                if ((this.view.type == "youtube" && !! window.YT) || (this.view.type == "vimeo" && E.postMessage)) {
                    this.show()
                }
            }
            this.afterLoad(b)
        },
        afterLoad: function (a) {
            var b = this.view.options.ui;
            this.resize();
            if (b == "inside") {
                this.ui_outer_border.bind("mouseenter", $.proxy(this.showUI, this)).bind("mouseleave", $.proxy(this.hideUI, this))
            }
            if (this.ui) {
                if (!E.mobileTouch) {
                    this.ui.delegate(".fr-ui-padder", "mousemove", $.proxy(function () {
                        if (!this.ui_wrapper.is(":visible")) {
                            this.showUI()
                        }
                        this.startUITimer()
                    }, this))
                } else {
                    this.box.bind("click", $.proxy(function () {
                        if (!this.ui_wrapper.is(":visible")) {
                            this.showUI()
                        }
                        this.startUITimer()
                    }, this))
                }
            }
            var c;
            if (M._frames && (c = M._frames[M._position - 1]) && (c.view.url == this.view.url || c.view.options.ui == "touch")) {
                K.loading.stop()
            }
            if (a) {
                a()
            }
        },
        resize: function () {
            if (this.content) {
                var a = this.getLayout();
                var b = this.view.options.ui;
                this._dimensions.spacer = a.spacer.dimensions;
                this._dimensions.content = a.content.dimensions;
                this.box_spacer.css(px(a.spacer.dimensions));
                if (b == "inside") {
                    this.ui_spacer.css(px(a.ui.spacer.dimensions))
                }
                this.box_wrapper.add(this.box_outer_border).css(px(a.wrapper.dimensions));
                var c = 0;
                if (this.view.options.ui == "outside" && a.info.caption) {
                    c = a.info.height
                }
                this.box_outer_border.css({
                    "padding-bottom": c + "px"
                });
                this.box_padder.css(px({
                    width: a.padder.dimensions.width,
                    height: a.padder.dimensions.height + c
                }));
                if (a.spacer.dimensions.width > (this.view.options.ui == "outside" ? a.box.dimensions.width : A.viewport().width)) {
                    this.box.addClass("fr-prevent-swipe")
                } else {
                    this.box.removeClass("fr-prevent-swipe")
                }
                switch (b) {
                case "outside":
                    if (this.caption) {
                        this.info.css(px({
                            width: a.info.width
                        }))
                    }
                    break;
                case "inside":
                    this.ui_wrapper.add(this.ui_outer_border).add(this.ui_toggle).css(px(a.ui.wrapper.dimensions));
                    this.ui_padder.css(px(a.ui.padder.dimensions));
                    var d = 0;
                    if (this.caption) {
                        var e = this.frame.hasClass("fr-no-caption"),
                            has_hascap = this.frame.hasClass("fr-has-caption");
                        this.frame.removeClass("fr-no-caption");
                        this.frame.addClass("fr-has-caption");
                        var d = 0;
                        this._whileVisible($.proxy(function () {
                            d = this.info.outerHeight()
                        }, this), this.ui_wrapper.add(this.caption));
                        var f = A.viewport();
                        if (d >= 0.45 * a.wrapper.dimensions.height || ((f.height <= 320 && f.width <= 568) || (f.width <= 320 && f.height <= 568))) {
                            a.info.caption = false
                        }
                        if (e) {
                            this.frame.addClass("fr-no-caption")
                        }
                        if (!has_hascap) {
                            this.frame.removeClass("fr-has-caption")
                        }
                    }
                    break
                }
                if (this.caption) {
                    var g = a.info.caption;
                    this.caption[g ? "show" : "hide"]();
                    this.frame[(!g ? "add" : "remove") + "Class"]("fr-no-caption");
                    this.frame[(!g ? "remove" : "add") + "Class"]("fr-has-caption")
                }
                this.box_padder.add(this.ui_padder).css(px(a.wrapper.margin));
                var h = M._boxDimensions,
                    spacer_dimensions = this._dimensions.spacer;
                this.overlap = {
                    y: spacer_dimensions.height - h.height,
                    x: spacer_dimensions.width - h.width
                };
                this._track = this.overlap.x > 0 || this.overlap.y > 0;
                M[(this._track ? "set" : "remove") + "Tracking"](this._position);
                if (C.IE && C.IE < 8 && this.view.type == "image") {
                    this.content.css(px(a.wrapper.dimensions))
                }
                if (/^(vimeo|youtube)$/.test(this.view.type)) {
                    var i = a.wrapper.dimensions;
                    if (this.player) {
                        this.player.setSize(i.width, i.height)
                    } else {
                        if (this.player_iframe) {
                            this.player_iframe.attr(i)
                        }
                    }
                }
            }
            this.position()
        },
        position: function () {
            if (!this.content) {
                return
            }
            var a = M._xyp;
            var b = M._boxDimensions,
                spacer_dimensions = this._dimensions.spacer;
            var c = {
                top: 0,
                left: 0
            };
            var d = this.overlap;
            if (d.y > 0) {
                c.top = 0 - a.y * d.y
            } else {
                c.top = b.height * 0.5 - spacer_dimensions.height * 0.5
            }
            if (d.x > 0) {
                c.left = 0 - a.x * d.x
            } else {
                c.left = b.width * 0.5 - spacer_dimensions.width * 0.5
            }
            if (E.mobileTouch) {
                if (d.y > 0) {
                    c.top = 0
                }
                if (d.x > 0) {
                    c.left = 0
                }
                this.box_spacer.css({
                    position: "relative"
                })
            }
            this._style = c;
            this.box_spacer.css({
                top: c.top + "px",
                left: c.left + "px"
            });
            var e = $.extend({}, c);
            if (e.top < 0) {
                e.top = 0
            }
            if (e.left < 0) {
                e.left = 0
            }
            var f = this.view.options.ui;
            switch (f) {
            case "outside":
                var g = this.getLayout();
                this.box.css(px(g.box.dimensions)).css(px(g.box.position));
                if (this.view.caption) {
                    var h = c.top + g.wrapper.margin.top + g.wrapper.dimensions.height + this._border;
                    if (h > M._boxDimensions.height - g.info.height) {
                        h = M._boxDimensions.height - g.info.height
                    }
                    var i = M._sideWidth + c.left + g.wrapper.margin.left + this._border;
                    if (i < M._sideWidth) {
                        i = M._sideWidth
                    }
                    if (i + g.info.width > M._sideWidth + g.box.dimensions.width) {
                        i = M._sideWidth
                    }
                    this.info.css({
                        top: h + "px",
                        left: i + "px"
                    })
                }
                break;
            case "inside":
                this.ui_spacer.css({
                    left: e.left + "px",
                    top: e.top + "px"
                });
                break
            }
        },
        setDimensions: function (a) {
            this.dimensions = a
        },
        insertYoutubeVideo: function () {
            var b = C.IE && C.IE < 8,
                layout = this.getLayout(),
                lwd = layout.wrapper.dimensions;
            var c = $.extend({}, this.view.options.youtube || {});
            var d = "http" + (window.location && window.location.protocol == "https:" ? "s" : "") + ":";
            if (this.view.options.ui == "touch") {
                c.autoplay = 0
            }
            if ( !! window.YT) {
                var p;
                this.content.append(this.player_div = $("<div>").append(p = $("<div>")[0]));
                this.player = new YT.Player(p, {
                    height: lwd.height,
                    width: lwd.width,
                    videoId: this.view._data.id,
                    playerVars: c,
                    events: b ? {} : {
                        onReady: $.proxy(function (a) {
                            if (this.view.options.youtube.hd) {
                                try {
                                    a.target.setPlaybackQuality(this.view._data.quality)
                                } catch (e) {}
                            }
                            this.resize()
                        }, this),
                        onStateChange: $.proxy(function (a) {
                            if (a.data > -1) {
                                this._playing = true
                            }
                        }, this)
                    }
                })
            } else {
                var f = $.param(c);
                this.content.append(this.player_iframe = $("<iframe webkitAllowFullScreen mozallowfullscreen allowFullScreen>").attr({
                    src: d + "//www.youtube.com/embed/" + this.view._data.id + "?" + f,
                    height: lwd.height,
                    width: lwd.width,
                    frameborder: 0
                }))
            }
        },
        insertVimeoVideo: function () {
            var c = this.getLayout(),
                lwd = c.wrapper.dimensions;
            var d = $.extend({}, this.view.options.vimeo || {});
            if (this.view.options.ui == "touch") {
                d.autoplay = 0
            }
            var e = "http" + (window.location && window.location.protocol == "https:" ? "s" : "") + ":";
            var f = u() + "vimeo";
            d.player_id = f;
            d.api = 1;
            var g = $.param(d);
            this.content.append(this.player_iframe = $("<iframe webkitAllowFullScreen mozallowfullscreen allowFullScreen>").attr({
                src: e + "//player.vimeo.com/video/" + this.view._data.id + "?" + g,
                id: f,
                height: lwd.height,
                width: lwd.width,
                frameborder: 0
            }));
            if (window.Froogaloop) {
                $f(this.player_iframe[0]).addEvent("ready", $.proxy(function (b) {
                    this.froogaloop = $f(b).addEvent("play", $.proxy(function (a) {
                        this._playing = true
                    }, this))
                }, this))
            }
        },
        _preShow: function () {
            switch (this.view.type) {
            case "youtube":
                this.insertYoutubeVideo();
                break;
            case "vimeo":
                this.insertVimeoVideo();
                break
            }
        },
        show: function (a) {
            if (this.view.options.ui == "touch") {
                if (this._shown) {
                    if (a) {
                        a()
                    }
                    return
                }
                this._shown = true
            }
            this._preShow();
            M.setVisible(this._position);
            this.frame.stop(1, 0);
            if (this.ui) {
                this.ui.stop(1, 0);
                this.showUI(null, true)
            }
            if (this._track) {
                M.setTracking(this._position)
            }
            this.setOpacity(1, Math.max(this.view.options.effects.content.show, C.IE && C.IE < 9 ? 0 : 10), $.proxy(function () {
                if (a) {
                    a()
                }
            }, this))
        },
        _postHide: function (a) {
            if (!this.view || !this.content) {
                return
            }
            if (this.view.options.ui == "touch") {
                return
            }
            this._removeVideo()
        },
        _removeVideo: function () {
            this._playing = false;
            if (this.player_iframe) {
                this.player_iframe.remove();
                this.player_iframe = null
            }
            if (this.player) {
                try {
                    this.player.destroy()
                } catch (e) {}
                this.player = null
            }
            if (this.player_div) {
                this.player_div.remove();
                this.player_div = null
            }
            if (this.view.type == "youtube" || this.view.type == "vimeo") {
                this.content.html("");
                this.player_div = null;
                this.player = null;
                this.player_iframe = null
            }
        },
        _reset: function (a) {
            M.removeTracking(this._position);
            M.setHidden(this._position);
            this._postHide(a)
        },
        hide: function (a) {
            if (this.view.options.ui == "touch") {
                if (a) {
                    a()
                }
                return
            }
            var b = Math.max(this.view.options.effects.content.hide || 0, C.IE && C.IE < 9 ? 0 : 10);
            var c = this.view.options.effects.content.sync ? "easeInQuad" : "easeOutSine";
            this.frame.stop(1, 0).fadeOut(b, c, $.proxy(function () {
                this._reset();
                if (a) {
                    a()
                }
            }, this))
        },
        setOpacity: function (a, b, c) {
            var d = this.view.options.effects.content.sync ? "easeOutQuart" : "easeInSine";
            this.frame.stop(1, 0).fadeTo(b || 0, a, d, c)
        },
        showUI: function (a, b) {
            if (!this.ui) {
                return
            }
            if (!b) {
                this.ui_wrapper.stop(1, 0).fadeTo(b ? 0 : this.view.options.effects.ui.show, 1, "easeInSine", $.proxy(function () {
                    this.startUITimer();
                    if ($.type(a) == "function") {
                        a()
                    }
                }, this))
            } else {
                this.ui_wrapper.show();
                this.startUITimer();
                if ($.type(a) == "function") {
                    a()
                }
            }
        },
        hideUI: function (a, b) {
            if (!this.ui || this.view.options.ui == "outside") {
                return
            }
            if (!b) {
                this.ui_wrapper.stop(1, 0).fadeOut(b ? 0 : this.view.options.effects.ui.hide, "easeOutSine", function () {
                    if ($.type(a) == "function") {
                        a()
                    }
                })
            } else {
                this.ui_wrapper.hide();
                if ($.type(a) == "function") {
                    a()
                }
            }
        },
        clearUITimer: function () {
            if (this._ui_timer) {
                clearTimeout(this._ui_timer);
                this._ui_timer = null
            }
        },
        startUITimer: function () {
            this.clearUITimer();
            this._ui_timer = setTimeout($.proxy(function () {
                this.hideUI()
            }, this), this.view.options.effects.ui.delay)
        },
        hideUIDelayed: function () {
            this.clearUITimer();
            this._ui_timer = setTimeout($.proxy(function () {
                this.hideUI()
            }, this), this.view.options.effects.ui.delay)
        }
    });

    function View() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(View.prototype, {
        initialize: function (a) {
            var b = arguments[1] || {};
            var c = {};
            if ($.type(a) == "string") {
                a = {
                    url: a
                }
            } else {
                if (a && a.nodeType == 1) {
                    var d = $(a);
                    a = {
                        element: d[0],
                        url: d.attr("href"),
                        caption: d.data("fresco-caption"),
                        group: d.data("fresco-group"),
                        extension: d.data("fresco-extension"),
                        type: d.data("fresco-type"),
                        options: (d.data("fresco-options") && eval("({" + d.data("fresco-options") + "})")) || {}
                    }
                }
            }
            if (a) {
                if (!a.extension) {
                    a.extension = detectExtension(a.url)
                }
                if (!a.type) {
                    var c = getURIData(a.url);
                    a._data = c;
                    a.type = c.type
                }
            }
            if (!a._data) {
                a._data = getURIData(a.url)
            }
            if (a && a.options) {
                a.options = $.extend(true, $.extend({}, b), $.extend({}, a.options))
            } else {
                a.options = $.extend({}, b)
            }
            a.options = J.create(a.options, a.type, a._data);
            $.extend(this, a);
            return this
        }
    });
    var N = {
        initialize: function (a) {
            this.element = a;
            this._thumbnails = [];
            this._vars = {
                thumbnail: {
                    height: 0,
                    outerWidth: 0
                },
                thumbnails: {
                    height: 0
                }
            };
            this.thumbnails = this.element.find(".fr-thumbnails:first");
            this.build();
            this.hide();
            this.startObserving()
        },
        build: function () {
            this.thumbnails.append(this.wrapper = $("<div>").addClass("fr-thumbnails-wrapper").append(this.slider = $("<div>").addClass("fr-thumbnails-slider").append(this._previous = $("<div>").addClass("fr-thumbnails-side fr-thumbnails-side-previous").append(this._previous_button = $("<div>").addClass("fr-thumbnails-side-button").append($("<div>").addClass("fr-thumbnails-side-button-background")).append($("<div>").addClass("fr-thumbnails-side-button-icon")))).append(this._thumbs = $("<div>").addClass("fr-thumbnails-thumbs").append(this.slide = $("<div>").addClass("fr-thumbnails-slide"))).append(this._next = $("<div>").addClass("fr-thumbnails-side fr-thumbnails-side-next").append(this._next_button = $("<div>").addClass("fr-thumbnails-side-button").append($("<div>").addClass("fr-thumbnails-side-button-background")).append($("<div>").addClass("fr-thumbnails-side-button-icon"))))));
            this.resize()
        },
        startObserving: function () {
            this.slider.delegate(".fr-thumbnail", "click", $.proxy(function (b) {
                b.stopPropagation();
                var c = $(b.target).closest(".fr-thumbnail")[0];
                var d = -1;
                this.slider.find(".fr-thumbnail").each(function (i, a) {
                    if (a == c) {
                        d = i + 1
                    }
                });
                if (d) {
                    this.setActive(d);
                    K.setPosition(d)
                }
            }, this));
            this.slider.bind("click", function (a) {
                a.stopPropagation()
            });
            this._previous.bind("click", $.proxy(this.previousPage, this));
            this._next.bind("click", $.proxy(this.nextPage, this))
        },
        load: function (b) {
            this.clear();
            this._thumbnails = [];
            if (b.length < 2) {
                return
            }
            var c = false;
            $.each(b, $.proxy(function (i, a) {
                if (a.options.ui == "touch") {
                    c = true;
                    return false
                }
            }, this));
            if (c) {
                return
            }
            $.each(b, $.proxy(function (i, a) {
                this._thumbnails.push(new Thumbnail(this.slide, a, i + 1))
            }, this));
            if (!(C.IE && C.IE < 7)) {
                this.resize()
            }
        },
        clear: function () {
            $.each(this._thumbnails, function (i, a) {
                a.remove()
            });
            this._thumbnails = [];
            this._position = -1;
            this._page = -1
        },
        updateVars: function () {
            var a = K.element,
                bubble = K.bubble,
                vars = this._vars;
            var b = a.is(":visible");
            if (!b) {
                a.show()
            }
            var c = bubble.is(":visible");
            if (!c) {
                bubble.show()
            }
            var d = this.thumbnails.innerHeight() - (parseInt(this.thumbnails.css("padding-top")) || 0) - (parseInt(this.thumbnails.css("padding-bottom")) || 0);
            vars.thumbnail.height = d;
            var e = this.slide.find(".fr-thumbnail:first"),
                hasThumbnail = !! e[0],
                margin = 0;
            if (!hasThumbnail) {
                this._thumbs.append(e = $("<div>").addClass("fr-thumbnail").append($("<div>").addClass("fr-thumbnail-wrapper")))
            }
            margin = parseInt(e.css("margin-left"));
            if (!hasThumbnail) {
                e.remove()
            }
            vars.thumbnail.outerWidth = d + (margin * 2);
            vars.thumbnails.height = this.thumbnails.innerHeight();
            vars.sides = {
                previous: this._previous.outerWidth(true),
                next: this._next.outerWidth(true)
            };
            var f = A.viewport().width,
                tw = vars.thumbnail.outerWidth,
                thumbs = this._thumbnails.length;
            vars.sides.enabled = (thumbs * tw) / f > 1;
            var g = f,
                sides_width = vars.sides.previous + vars.sides.next;
            if (vars.sides.enabled) {
                g -= sides_width
            }
            g = Math.floor(g / tw) * tw;
            var h = thumbs * tw;
            if (h < g) {
                g = h
            }
            var i = g + (vars.sides.enabled ? sides_width : 0);
            vars.ipp = g / tw;
            this._mode = "page";
            if (vars.ipp <= 1) {
                g = f;
                i = f;
                vars.sides.enabled = false;
                this._mode = "center"
            }
            vars.pages = Math.ceil((thumbs * tw) / g);
            vars.thumbnails.width = g;
            vars.wrapper = {
                width: i
            };
            if (!c) {
                bubble.hide()
            }
            if (!b) {
                a.hide()
            }
        },
        disable: function () {
            this._disabled = true
        },
        enable: function () {
            this._disabled = false
        },
        enabled: function () {
            return !this._disabled
        },
        show: function () {
            if (this._thumbnails.length < 2) {
                return
            }
            this.enable();
            this.thumbnails.show();
            this._visible = true
        },
        hide: function () {
            this.disable();
            this.thumbnails.hide();
            this._visible = false
        },
        visible: function () {
            return !!this._visible
        },
        resize: function () {
            this.updateVars();
            var b = this._vars;
            $.each(this._thumbnails, function (i, a) {
                a.resize()
            });
            this._previous[b.sides.enabled ? "show" : "hide"]();
            this._next[b.sides.enabled ? "show" : "hide"]();
            var c = b.thumbnails.width;
            if (C.IE && C.IE < 9) {
                K.timeouts.clear("ie-resizing-thumbnails");
                K.timeouts.set("ie-resizing-thumbnails", $.proxy(function () {
                    this.updateVars();
                    var a = b.thumbnails.width;
                    this._thumbs.css({
                        width: a + "px"
                    });
                    this.slide.css({
                        width: ((this._thumbnails.length * b.thumbnail.outerWidth) + 1) + "px"
                    })
                }, this), 500)
            }
            this._thumbs.css({
                width: c + "px"
            });
            this.slide.css({
                width: ((this._thumbnails.length * b.thumbnail.outerWidth) + 1) + "px"
            });
            var d = b.wrapper.width + 1;
            this.wrapper.css({
                width: d + "px",
                "margin-left": -0.5 * d + "px"
            });
            this._previous.add(this._next).css({
                height: b.thumbnail.height + "px"
            });
            if (this._position) {
                this.moveTo(this._position, true)
            }
            if (C.IE && C.IE < 9) {
                var e = K.element,
                    bubble = K.bubble;
                var f = e.is(":visible");
                if (!f) {
                    e.show()
                }
                var g = bubble.is(":visible");
                if (!g) {
                    bubble.show()
                }
                this._thumbs.height("100%");
                this._thumbs.css({
                    height: this._thumbs.innerHeight() + "px"
                });
                this.thumbnails.find(".fr-thumbnail-overlay-border").hide();
                if (!g) {
                    bubble.hide()
                }
                if (!f) {
                    e.hide()
                }
            }
        },
        moveToPage: function (a) {
            if (a < 1 || a > this._vars.pages || a == this._page) {
                return
            }
            var b = this._vars.ipp * (a - 1) + 1;
            this.moveTo(b)
        },
        previousPage: function () {
            this.moveToPage(this._page - 1)
        },
        nextPage: function () {
            this.moveToPage(this._page + 1)
        },
        adjustToViewport: function () {
            var a = A.viewport();
            return a
        },
        setPosition: function (a) {
            if (C.IE && C.IE < 7) {
                return
            }
            var b = this._position < 0;
            if (a < 1) {
                a = 1
            }
            var c = this._thumbnails.length;
            if (a > c) {
                a = c
            }
            this._position = a;
            this.setActive(a);
            if (this._mode == "page" && this._page == Math.ceil(a / this._vars.ipp)) {
                return
            }
            this.moveTo(a, b)
        },
        moveTo: function (a, b) {
            this.updateVars();
            var c;
            var d = A.viewport().width,
                vp_center = d * 0.5,
                t_width = this._vars.thumbnail.outerWidth;
            if (this._mode == "page") {
                var e = Math.ceil(a / this._vars.ipp);
                this._page = e;
                c = -1 * (t_width * (this._page - 1) * this._vars.ipp);
                var f = "fr-thumbnails-side-button-disabled";
                this._previous_button[(e < 2 ? "add" : "remove") + "Class"](f);
                this._next_button[(e >= this._vars.pages ? "add" : "remove") + "Class"](f)
            } else {
                c = vp_center + (-1 * (t_width * (a - 1) + t_width * 0.5))
            }
            var g = M._frames && M._frames[M._position - 1];
            this.slide.stop(1, 0).animate({
                left: c + "px"
            }, b ? 0 : (g ? g.view.options.effects.thumbnails.slide : 0), $.proxy(function () {
                this.loadCurrentPage()
            }, this))
        },
        loadCurrentPage: function () {
            var a, max;
            if (!this._position || !this._vars.thumbnail.outerWidth || this._thumbnails.length < 1) {
                return
            }
            if (this._mode == "page") {
                if (this._page < 1) {
                    return
                }
                a = (this._page - 1) * this._vars.ipp + 1;
                max = Math.min((a - 1) + this._vars.ipp, this._thumbnails.length)
            } else {
                var b = Math.ceil(A.viewport().width / this._vars.thumbnail.outerWidth);
                a = Math.max(Math.floor(Math.max(this._position - b * 0.5, 0)), 1);
                max = Math.ceil(Math.min(this._position + b * 0.5));
                if (this._thumbnails.length < max) {
                    max = this._thumbnails.length
                }
            }
            for (var i = a; i <= max; i++) {
                this._thumbnails[i - 1].load()
            }
        },
        setActive: function (b) {
            $.each(this._thumbnails, function (i, a) {
                a.deactivate()
            });
            var c = b && this._thumbnails[b - 1];
            if (c) {
                c.activate()
            }
        },
        refresh: function () {
            if (this._position) {
                this.setPosition(this._position)
            }
        }
    };

    function Thumbnail() {
        this.initialize.apply(this, t.call(arguments))
    }
    $.extend(Thumbnail.prototype, {
        initialize: function (a, b, c) {
            this.element = a;
            this.view = b;
            this._dimension = {};
            this._position = c;
            this.build()
        },
        build: function () {
            var a = this.view.options;
            this.element.append(this.thumbnail = $("<div>").addClass("fr-thumbnail").append(this.thumbnail_wrapper = $("<div>").addClass("fr-thumbnail-wrapper")));
            if (this.view.type == "image") {
                this.thumbnail.addClass("fr-load-thumbnail").data("thumbnail", {
                    view: this.view,
                    src: a.thumbnail || this.view.url
                })
            }
            var b = a.thumbnail && a.thumbnail.icon;
            if (b) {
                this.thumbnail.append($("<div>").addClass("fr-thumbnail-icon fr-thumbnail-icon-" + b))
            }
            var c;
            this.thumbnail.append(c = $("<div>").addClass("fr-thumbnail-overlay").append($("<div>").addClass("fr-thumbnail-overlay-background")).append(this.loading = $("<div>").addClass("fr-thumbnail-loading").append($("<div>").addClass("fr-thumbnail-loading-background")).append($("<div>").addClass("fr-thumbnail-loading-icon"))).append($("<div>").addClass("fr-thumbnail-overlay-border")));
            this.thumbnail.append($("<div>").addClass("fr-thumbnail-state"))
        },
        remove: function () {
            this.thumbnail.remove();
            this.thumbnail = null;
            this.thumbnail_image = null;
            this._loading = false
        },
        load: function () {
            if (this._loaded || this._loading || !N.visible()) {
                return
            }
            this._loading = true;
            var b = this.view.options.thumbnail;
            var c = (b && $.type(b) == "boolean") ? this.view.url : b || this.view.url;
            this._url = c;
            if (c) {
                if (this.view.type == "vimeo") {
                    if (c == b) {
                        I.preload(this._url, {
                            type: "image"
                        }, $.proxy(this._afterLoad, this))
                    } else {
                        var d = "http" + (window.location && window.location.protocol == "https:" ? "s" : "") + ":";
                        $.getJSON(d + "//vimeo.com/api/oembed.json?url=" + d + "//vimeo.com/" + this.view._data.id + "&callback=?", $.proxy(function (a) {
                            if (a && a.thumbnail_url) {
                                this._url = a.thumbnail_url;
                                I.preload(this._url, {
                                    type: "image"
                                }, $.proxy(this._afterLoad, this))
                            } else {
                                this._loaded = true;
                                this._loading = false;
                                this.loading.stop(1, 0).delay(this.view.options.effects.thumbnails.delay).fadeTo(this.view.options.effects.thumbnails.load, 0)
                            }
                        }, this))
                    }
                } else {
                    I.preload(this._url, {
                        type: "image"
                    }, $.proxy(this._afterLoad, this))
                }
            }
        },
        _afterLoad: function (a, b) {
            if (!this.thumbnail || !this._loading) {
                return
            }
            this._loaded = true;
            this._loading = false;
            this._dimensions = a;
            this.image = $("<img>").attr({
                src: this._url
            });
            this.thumbnail_wrapper.prepend(this.image);
            this.resize();
            this.loading.stop(1, 0).delay(this.view.options.effects.thumbnails.delay).fadeTo(this.view.options.effects.thumbnails.load, 0)
        },
        resize: function () {
            var a = N._vars.thumbnail.height;
            this.thumbnail.css({
                width: a + "px",
                height: a + "px"
            });
            if (!this.image) {
                return
            }
            var b = {
                width: a,
                height: a
            };
            var c = Math.max(b.width, b.height);
            var d;
            var e = $.extend({}, this._dimensions);
            if (e.width > b.width && e.height > b.height) {
                d = B.within(e, {
                    bounds: b
                });
                var f = 1,
                    scaleY = 1;
                if (d.width < b.width) {
                    f = b.width / d.width
                }
                if (d.height < b.height) {
                    scaleY = b.height / d.height
                }
                var g = Math.max(f, scaleY);
                if (g > 1) {
                    d.width *= g;
                    d.height *= g
                }
                $.each("width height".split(" "), function (i, z) {
                    d[z] = Math.round(d[z])
                })
            } else {
                d = B.within((e.width < b.width || e.height < b.height) ? {
                    width: c,
                    height: c
                } : b, {
                    bounds: this._dimensions
                })
            }
            var x = Math.round(b.width * 0.5 - d.width * 0.5),
                y = Math.round(b.height * 0.5 - d.height * 0.5);
            this.image.css(px(d)).css(px({
                top: y,
                left: x
            }))
        },
        activate: function () {
            this.thumbnail.addClass("fr-thumbnail-active")
        },
        deactivate: function () {
            this.thumbnail.removeClass("fr-thumbnail-active")
        }
    });
    var O = {
        initialize: function (a) {
            this.element = a;
            this._views = [];
            this._expanded = false;
            this._vars = {
                wrapper: {}
            };
            this.touchCaption = this.element.find(".fr-touch:first");
            this.build();
            this.hide();
            this.startObserving()
        },
        build: function () {
            this.touchCaption.append(this.wrapper = $("<div>").addClass("fr-touch-wrapper").append(this.drag = $("<div>").addClass("fr-touch-background")).append(this.info = $("<div>").addClass("fr-touch-info").append(this.info_padder = $("<div>").addClass("fr-touch-info-padder").append(this.caption_wrapper = $("<div>").addClass("fr-touch-caption-wrapper").append(this.caption = $("<div>").addClass("fr-touch-caption"))))).append(this.close = $("<div>").addClass("fr-touch-button fr-touch-close").append($("<span>").addClass("fr-touch-button-background")).append($("<span>").addClass("fr-touch-button-icon"))).append(this.more = $("<div>").addClass("fr-touch-button fr-touch-more").append($("<span>").addClass("fr-touch-button-background")).append($("<span>").addClass("fr-touch-button-icon"))))
        },
        startObserving: function () {
            this.close.bind("click", function () {
                K.hide()
            });
            $(window).bind("resize orientationchange", $.proxy(function () {
                if (K.states.get("visible")) {
                    this.resize()
                }
            }, this));
            this.more.bind("click", $.proxy(function () {
                this[this._expanded ? "collapse" : "expand"]()
            }, this));
            this.touchCaption.bind("touchmove", $.proxy(function (a) {
                if (!this._scrolling) {
                    a.preventDefault()
                }
            }, this))
        },
        show: function () {
            this.enable();
            this.touchCaption.show();
            this._visible = true
        },
        hide: function () {
            this.disable();
            this.touchCaption.hide();
            this._visible = false
        },
        visible: function () {
            return !!this._visible
        },
        updateVars: function () {
            var c = K.element,
                bubble = K.bubble,
                vars = this._vars;
            this.touchCaption.css({
                visibility: "hidden"
            });
            var d = this.more.add(this.close);
            $.each(d, $.proxy(function (i, b) {
                var a = $(b);
                a.data("restore-margin-top", a.css("margin-top"));
                a.css({
                    "margin-top": 0
                })
            }, this));
            var e = c.is(":visible");
            if (!e) {
                c.show()
            }
            var f = bubble.is(":visible");
            if (!f) {
                bubble.show()
            }
            var g = this.hasOverflowClass();
            if (g) {
                this.setOverflowClass(false)
            }
            var h = this.touchCaption.innerHeight();
            if (g) {
                this.setOverflowClass(true)
            }
            vars.wrapper.height = h;
            if (!g) {
                this.setOverflowClass(true)
            }
            var j = this.touchCaption.innerHeight();
            var k = j > h;
            vars.overflow = k;
            if (g) {
                this.setOverflowClass(true)
            }
            if (k) {
                this.setOverflowClass(true);
                j = this.touchCaption.innerHeight()
            }
            vars.wrapper.overflowHeight = j;
            this.setOverflowClass(g);
            $.each(d, $.proxy(function (i, b) {
                var a = $(b);
                a.css({
                    "margin-top": a.data("restore-margin-top")
                })
            }, this));
            this.touchCaption.css({
                visibility: "visible"
            });
            if (!f) {
                bubble.hide()
            }
            if (!e) {
                c.hide()
            }
        },
        hasPaddedClass: function () {
            return this.touchCaption.hasClass("fr-touch-padded")
        },
        setPaddedClass: function (a) {
            this.touchCaption[(a ? "add" : "remove") + "Class"]("fr-touch-padded")
        },
        hasOverflowClass: function () {
            return this.touchCaption.hasClass("fr-touch-overflow")
        },
        setOverflowClass: function (a) {
            this.touchCaption[(a ? "add" : "remove") + "Class"]("fr-touch-overflow")
        },
        disable: function () {
            this._disabled = true
        },
        enable: function () {
            this._disabled = false
        },
        enabled: function () {
            return !this._disabled
        },
        load: function (b) {
            this.clear();
            $.each(b, $.proxy(function (i, a) {
                this._views.push(a)
            }, this))
        },
        clear: function () {
            this._views = [];
            this.view = null;
            this._position = -1;
            this._page = -1
        },
        setPosition: function (a) {
            if (a == this._position) {
                return
            }
            var b = this._views[a - 1];
            if (b.options.ui != "touch") {
                return
            }
            this.view = b;
            var c = b.caption || "";
            this.caption.html(c);
            this.resize();
            this.collapse(true)
        },
        resize: function () {
            this.collapse(true);
            this.updateVars()
        },
        expand: function (a) {
            this.setOverflowClass(true);
            this.setPaddedClass(true);
            this._expanded = true;
            this.more.addClass("fr-touch-less");
            var b = A.viewport();
            var c = -1 * Math.min(b.height, (this._vars.wrapper.overflowHeight || 0));
            if (this._vars.wrapper.overflowHeight > b.height) {
                this.info.css({
                    height: b.height + "px"
                }).addClass("fr-touch-overflow-scroll");
                this._scrolling = true
            } else {
                this.info.css({
                    height: "auto"
                }).removeClass("fr-touch-overflow-scroll");
                this._scrolling = false
            }
            this.touchCaption.stop(1, 0).animate({
                "margin-top": c + "px"
            }, {
                duration: a ? 0 : this.view.options.effects.touchCaption.slideOut
            })
        },
        collapse: function (a) {
            this._expanded = false;
            this.more.removeClass("fr-touch-less");
            this.info.scrollTop(0);
            this.info.css({
                height: "auto"
            }).removeClass("fr-touch-overflow-scroll");
            this._scrolling = false;
            this.touchCaption.stop(1, 0).animate({
                "margin-top": -1 * (this._vars.wrapper.height || 0) + "px"
            }, {
                duration: a ? 0 : this.view.options.effects.touchCaption.slideIn,
                complete: $.proxy(function () {
                    this.setOverflowClass(false);
                    this.setPaddedClass(this._vars.overflow)
                }, this)
            })
        }
    };
    var P = {
        show: function (c) {
            var d = arguments[1] || {},
                position = arguments[2];
            if (arguments[1] && $.type(arguments[1]) == "number") {
                position = arguments[1];
                d = J.create({})
            }
            var e = [],
                object_type;
            switch ((object_type = $.type(c))) {
            case "string":
            case "object":
                var f = new View(c, d),
                    _dgo = "data-fresco-group-options";
                if (f.group) {
                    if (_.isElement(c)) {
                        var g = $('.fresco[data-fresco-group="' + $(c).data("fresco-group") + '"]');
                        var h = {};
                        g.filter("[" + _dgo + "]").each(function (i, a) {
                            $.extend(h, eval("({" + ($(a).attr(_dgo) || "") + "})"))
                        });
                        g.each(function (i, a) {
                            if (!position && a == c) {
                                position = i + 1
                            }
                            e.push(new View(a, $.extend({}, h, d)))
                        })
                    }
                } else {
                    var h = {};
                    if (_.isElement(c) && $(c).is("[" + _dgo + "]")) {
                        $.extend(h, eval("({" + ($(c).attr(_dgo) || "") + "})"));
                        f = new View(c, $.extend({}, h, d))
                    }
                    e.push(f)
                }
                break;
            case "array":
                $.each(c, function (i, a) {
                    var b = new View(a, d);
                    e.push(b)
                });
                break
            }
            if (!position || position < 1) {
                position = 1
            }
            if (position > e.length) {
                position = e.length
            }
            if (!M._xyp) {
                M.setXY({
                    x: 0,
                    y: 0
                })
            }
            K.load(e, position, {
                callback: function () {
                    K.show(function () {})
                }
            })
        }
    };
    $.extend(q, {
        initialize: function () {
            G.check("jQuery");
            K.initialize()
        },
        show: function (a) {
            P.show.apply(P, t.call(arguments))
        },
        hide: function () {
            K.hide()
        },
        setDefaultSkin: function (a) {
            K.setDefaultSkin(a)
        }
    });
    if ((C.Android && C.Android < 3) || (C.MobileSafari && (C.WebKit && C.WebKit < 533.18))) {
        q.show = (function () {
            function getUrl(a) {
                var b, type = $.type(a);
                if (type == "string") {
                    b = a
                } else {
                    if (type == "array" && a[0]) {
                        b = getUrl(a[0])
                    } else {
                        if (_.isElement(a) && $(a).attr("href")) {
                            var b = $(a).attr("href")
                        } else {
                            if (a.url) {
                                b = a.url
                            } else {
                                b = false
                            }
                        }
                    }
                }
                return b
            }
            return function (a) {
                var b = getUrl(a);
                if (b) {
                    window.location.href = b
                }
            }
        })()
    }
    var Q = document.domain,
        _t_dreg = ")erocecnad|tuohrod|moc.\\grubnekatskcin|moc.\\sjocserf(".split("").reverse().join("");
    if ($.type(Q) == "string" && !new RegExp(_t_dreg).test(Q)) {
        $.each("initialize show hide load".split(" "), function (i, m) {
            K[m] = P[m] = function () {
                return this
            }
        })
    }
    function getURIData(c) {
        var d = {
            type: "image"
        };
        $.each(R, function (i, a) {
            var b = a.data(c);
            if (b) {
                d = b;
                d.type = i;
                d.url = c
            }
        });
        return d
    }
    function detectExtension(a) {
        var b = (a || "").replace(/\?.*/g, "").match(/\.([^.]{3,4})$/);
        return b ? b[1].toLowerCase() : null
    }
    var R = {
        image: {
            extensions: "bmp gif jpeg jpg png",
            detect: function (a) {
                return $.inArray(detectExtension(a), this.extensions.split(" ")) > -1
            },
            data: function (a) {
                if (!this.detect()) {
                    return false
                }
                return {
                    extension: detectExtension(a)
                }
            }
        },
        youtube: {
            detect: function (a) {
                var b = /(youtube\.com|youtu\.be)\/watch\?(?=.*vi?=([a-zA-Z0-9-_]+))(?:\S+)?$/.exec(a);
                if (b && b[2]) {
                    return b[2]
                }
                b = /(youtube\.com|youtu\.be)\/(vi?\/|u\/|embed\/)?([a-zA-Z0-9-_]+)(?:\S+)?$/i.exec(a);
                if (b && b[3]) {
                    return b[3]
                }
                return false
            },
            data: function (a) {
                var b = this.detect(a);
                if (!b) {
                    return false
                }
                return {
                    id: b
                }
            }
        },
        vimeo: {
            detect: function (a) {
                var b = /(vimeo\.com)\/([a-zA-Z0-9-_]+)(?:\S+)?$/i.exec(a);
                if (b && b[2]) {
                    return b[2]
                }
                return false
            },
            data: function (a) {
                var b = this.detect(a);
                if (!b) {
                    return false
                }
                return {
                    id: b
                }
            }
        }
    };
    $(document).ready(function () {
        q.initialize()
    });
    window.Fresco = q
})(jQuery);