var GDAssistant = /** @class */ (function () {
    function GDAssistant() {
        this.init();
    }
    GDAssistant.prototype.init = function () {
        this.backdropEl = document.getElementById("gd-assistant-backdrop");
        this.containerEl = document.getElementById("gd-assistant-container");
        this.addListeners();
        this.maybeOpenAssistant();
    };
    GDAssistant.prototype.toggleAssistant = function () {
        this.containerEl.classList.toggle("gd-assistant-open");
    };
    GDAssistant.prototype.maybeOpenAssistant = function () {
        var searchParams = new URLSearchParams(window.location.search);
        var prompt = searchParams.get("aiassistant");
        if (prompt) {
            var iframe = document.getElementById("gd-assistant-app");
            iframe.setAttribute("src", iframe.src + "&aiassistant=" + prompt);
            this.toggleAssistant();
        }
    };
    GDAssistant.prototype.addListeners = function () {
        var _this = this;
        document.addEventListener("click", function (event) {
            var _a, _b, _c, _d, _e;
            if (((_c = (_b = (_a = event.target) === null || _a === void 0 ? void 0 : _a.parentElement) === null || _b === void 0 ? void 0 : _b.classList) === null || _c === void 0 ? void 0 : _c.contains("gd-assistant-open")) ||
                ((_e = (_d = event.target) === null || _d === void 0 ? void 0 : _d.classList) === null || _e === void 0 ? void 0 : _e.contains("gd-assistant-open"))) {
                event.preventDefault();
                _this.toggleAssistant();
            }
        });
        window.addEventListener("keydown", function (event) {
            // do something when a key is pressed
            if ((event.ctrlKey || event.metaKey) && event.key === "j") {
                event.preventDefault();
                _this.toggleAssistant();
                setTimeout(function () {
                    var _a, _b, _c;
                    (_c = (_b = (_a = document.getElementById("gd-assistant-app")) === null || _a === void 0 ? void 0 : _a.contentWindow) === null || _b === void 0 ? void 0 : _b.document.getElementById("gd-assistant-chat-input")) === null || _c === void 0 ? void 0 : _c.focus();
                }, 100);
            }
        });
        this.backdropEl.addEventListener("click", function (event) {
            _this.toggleAssistant();
        });
        window.addEventListener("message", function (event) {
            var _a, _b, _c;
            if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.type) === "assistantRedirect") {
                if (((_b = event.data.payload) === null || _b === void 0 ? void 0 : _b.target) === "_blank") {
                    window.open(event.data.payload.url, "_blank");
                }
                else {
                    window.location.href = event.data.payload.url;
                }
            }
            else if (((_c = event.data) === null || _c === void 0 ? void 0 : _c.type) === "toggleAssistant") {
                _this.toggleAssistant();
                window.focus();
            }
        });
    };
    return GDAssistant;
}());
(function (window, document, $) {
    $(document).on("ready", function () { return new GDAssistant(); });
})(window, document, jQuery);
