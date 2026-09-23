const { PluginBaseClass } = window;

/**
 * Storefront chat widget.
 *
 * This plugin only handles the widget's UI (open/close, rendering messages).
 * Sending a message to the actual chatbot backend (FastAPI / RAG) is
 * deliberately NOT wired up here yet — that needs a server-side proxy route
 * so the backend API key never reaches the browser, and the actual chatbot
 * logic is being built separately, not by Claude on its own. See
 * ChatbotConfigService / ChatbotContextRoute for the existing plugin-side
 * config plumbing this widget will eventually talk to.
 */
export default class ChatbotWidgetPlugin extends PluginBaseClass {
    static options = {
        replyDelay: 900,
    };

    init() {
        this.pendingReplyText = this.el.dataset.chatbotWidgetPendingReply;

        this.toggleButton = this.el.querySelector('[data-chatbot-widget-toggle]');
        this.closeButton = this.el.querySelector('[data-chatbot-widget-close]');
        this.panel = this.el.querySelector('.chatbot-widget__panel');
        this.form = this.el.querySelector('[data-chatbot-widget-form]');
        this.input = this.el.querySelector('[data-chatbot-widget-input]');
        this.sendButton = this.el.querySelector('[data-chatbot-widget-send]');
        this.messages = this.el.querySelector('[data-chatbot-widget-messages]');

        this._registerEvents();
    }

    _registerEvents() {
        this.toggleButton.addEventListener('click', this._onToggle.bind(this));
        this.closeButton.addEventListener('click', this._onClose.bind(this));
        this.form.addEventListener('submit', this._onSubmit.bind(this));
        document.addEventListener('keydown', this._onKeydown.bind(this));
    }

    _onToggle() {
        const isOpen = this.el.classList.toggle('chatbot-widget--open');

        this.toggleButton.setAttribute('aria-expanded', String(isOpen));
        this.panel.setAttribute('aria-hidden', String(!isOpen));

        if (isOpen) {
            this.input.focus();
        }
    }

    _onClose() {
        this.el.classList.remove('chatbot-widget--open');
        this.toggleButton.setAttribute('aria-expanded', 'false');
        this.panel.setAttribute('aria-hidden', 'true');
    }

    _onKeydown(event) {
        if (event.key === 'Escape' && this.el.classList.contains('chatbot-widget--open')) {
            this._onClose();
            this.toggleButton.focus();
        }
    }

    _onSubmit(event) {
        event.preventDefault();

        const text = this.input.value.trim();

        if (!text) {
            return;
        }

        this._appendMessage(text, 'user');
        this.input.value = '';
        this._setInputDisabled(true);

        const typingBubble = this._appendTyping();

        // TODO: Hier später die Antwort über eine serverseitige Route holen,
        // die intern ChatbotConfigService nutzt und den API-Key gegenüber dem
        // FastAPI-Backend anhängt (nie direkt aus dem Browser heraus).
        window.setTimeout(() => {
            typingBubble.remove();
            this._appendMessage(this.pendingReplyText, 'bot');
            this._setInputDisabled(false);
            this.input.focus();
        }, this.options.replyDelay);
    }

    _appendMessage(text, role) {
        const bubble = document.createElement('div');
        bubble.classList.add('chatbot-widget__bubble', `chatbot-widget__bubble--${role}`);
        bubble.textContent = text;

        this.messages.appendChild(bubble);
        this._scrollToBottom();

        return bubble;
    }

    _appendTyping() {
        const bubble = document.createElement('div');
        bubble.classList.add('chatbot-widget__bubble', 'chatbot-widget__bubble--bot', 'chatbot-widget__bubble--typing');
        bubble.innerHTML = '<span></span><span></span><span></span>';

        this.messages.appendChild(bubble);
        this._scrollToBottom();

        return bubble;
    }

    _setInputDisabled(disabled) {
        this.input.disabled = disabled;
        this.sendButton.disabled = disabled;
    }

    _scrollToBottom() {
        this.messages.scrollTop = this.messages.scrollHeight;
    }
}
