const { PluginBaseClass } = window;

/**
 * Storefront chat widget.
 *
 * Sends messages to the plugin's own storefront proxy route
 * (`POST /widgets/chatbot-connector/message`), which forwards them
 * server-side to the FastAPI backend and attaches the backend API key there
 * (never in the browser). See:
 *   - ShopwareChatbotConnectorPlugin\Storefront\Controller\ChatbotController (PHP proxy)
 *   - fastapi-backend/app/routers/chat.py (actual backend endpoint)
 *
 * This is a plain storefront AJAX route (session-based), not a Store-API
 * route, so no sw-access-key is needed here.
 *
 * This plugin only handles the widget's UI and the HTTP call to our own
 * proxy route — no chatbot / RAG / LLM logic lives here.
 */
export default class ChatbotWidgetPlugin extends PluginBaseClass {
    static options = {
        messageRouteUrl: '/widgets/chatbot-connector/message',
        errorReplyText: 'Entschuldigung, der Chat ist gerade nicht erreichbar. Bitte versuch es später erneut.',
    };

    init() {
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

        this._sendMessage(text)
            .then((reply) => {
                typingBubble.remove();
                this._appendMessage(reply, 'bot');
            })
            .catch(() => {
                typingBubble.remove();
                this._appendMessage(this.options.errorReplyText, 'bot');
            })
            .finally(() => {
                this._setInputDisabled(false);
                this.input.focus();
            });
    }

    _sendMessage(text) {
        return fetch(this.options.messageRouteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: text }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Chatbot request failed with status ${response.status}`);
                }

                return response.json();
            })
            .then((data) => {
                if (typeof data.reply !== 'string') {
                    throw new Error('Chatbot response did not contain a reply.');
                }

                return data.reply;
            });
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
