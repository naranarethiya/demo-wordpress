class GDAssistant {
  backdropEl;
  containerEl;
  activateBtnEl;

  constructor() {
    this.init();
  }

  init() {
    this.backdropEl = document.getElementById("gd-assistant-backdrop");
    this.containerEl = document.getElementById("gd-assistant-container");
    this.addListeners();
    this.maybeOpenAssistant();
  }

  toggleAssistant() {
    this.containerEl.classList.toggle("gd-assistant-open");
  }

  maybeOpenAssistant() {
    const searchParams = new URLSearchParams(window.location.search);
    const prompt = searchParams.get("aiassistant");
    if (prompt) {
      const iframe = document.getElementById(
        "gd-assistant-app"
      ) as HTMLIFrameElement;
      iframe.setAttribute("src", iframe.src + "&aiassistant=" + prompt);
      this.toggleAssistant();
    }
  }

  addListeners() {
    document.addEventListener("click", (event: MouseEvent) => {
      if (
        (event.target as HTMLElement)?.parentElement?.classList?.contains(
          "gd-assistant-open"
        ) ||
        (event.target as HTMLElement)?.classList?.contains("gd-assistant-open")
      ) {
        event.preventDefault();
        this.toggleAssistant();
      }
    });
    window.addEventListener("keydown", (event) => {
      // do something when a key is pressed
      if ((event.ctrlKey || event.metaKey) && event.key === "j") {
        event.preventDefault();
        this.toggleAssistant();
        setTimeout(() => {
          (
            document.getElementById("gd-assistant-app") as HTMLIFrameElement
          )?.contentWindow?.document
            .getElementById("gd-assistant-chat-input")
            ?.focus();
        }, 100);
      }
    });

    this.backdropEl.addEventListener("click", (event) => {
      this.toggleAssistant();
    });

    window.addEventListener("message", (event) => {
      if (event.data?.type === "assistantRedirect") {
        if (event.data.payload?.target === "_blank") {
          window.open(event.data.payload.url, "_blank");
        } else {
          window.location.href = event.data.payload.url;
        }
      } else if (event.data?.type === "toggleAssistant") {
        this.toggleAssistant();
        window.focus();
      }
    });
  }
}

(function (window, document, $) {
  $(document).on("ready", () => new GDAssistant());
})(window, document, jQuery);
