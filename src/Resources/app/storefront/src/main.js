import ChatbotWidgetPlugin from './chatbot-widget/chatbot-widget.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('ChatbotWidget', ChatbotWidgetPlugin, '[data-chatbot-widget]');
