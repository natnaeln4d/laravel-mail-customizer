import Vue from 'vue';
import draggable from 'vuedraggable';
import BuilderToolbar from './components/Builder/Toolbar.vue';
import BuilderCanvas from './components/Builder/Canvas.vue';
import PropertiesPanel from './components/Builder/PropertiesPanel.vue';
import BuilderElement from './components/Builder/Element.vue';
import MailBuilder from './components/MailBuilder.vue';
import MailCustomizerVue from './components/MailCustomizer.vue';
import MailCustomizerReact from './components/MailCustomizerReact.jsx';

// Vue and axios (and React/ReactDOM, if used) are expected to be loaded by the host page.
// The webpack config marks them as externals so the bundle stays small and shares the host's copies.

const DEFAULT_VARIABLES = { name: 'Guest User', email: 'user@example.com' };

Vue.component('builder-toolbar', BuilderToolbar);
Vue.component('builder-canvas', BuilderCanvas);
Vue.component('properties-panel', PropertiesPanel);
Vue.component('builder-element', BuilderElement);
Vue.component('mail-builder', MailBuilder);
Vue.component('mail-customizer', MailCustomizerVue);
Vue.component('draggable', draggable);

function readOptions(element) {
    const templateId = element.getAttribute('data-template-id');
    let variables = DEFAULT_VARIABLES;

    try {
        const json = element.getAttribute('data-variables');
        if (json) variables = JSON.parse(json);
    } catch (e) {
        console.error('Failed to parse data-variables JSON:', e);
    }

    return { templateId: templateId ? parseInt(templateId, 10) : null, variables };
}

function mountVue(element, component, options = {}) {
    const tag = component === 'builder' ? 'mail-builder' : 'mail-customizer';

    return new Vue({
        el: element,
        data: {
            templateId: options.templateId || null,
            defaultVariables: options.variables || DEFAULT_VARIABLES
        },
        template: `<${tag} :template-id="templateId" :default-variables="defaultVariables"></${tag}>`
    });
}

export function initVue(elementId, options = {}) {
    const element = document.getElementById(elementId);
    if (!element) {
        console.error('Element not found:', elementId);
        return null;
    }
    return mountVue(element, options.component || 'builder', options);
}

export function initReact(elementId, options = {}) {
    if (typeof window.React === 'undefined' || typeof window.ReactDOM === 'undefined') {
        console.error('React and ReactDOM must be loaded before initializing the MailCustomizer React component');
        return null;
    }

    const element = document.getElementById(elementId);
    if (!element) {
        console.error('Element not found:', elementId);
        return null;
    }

    const component = window.React.createElement(MailCustomizerReact, {
        templateId: options.templateId || null,
        defaultVariables: options.variables || DEFAULT_VARIABLES
    });

    window.ReactDOM.render(component, element);
    return component;
}

// Auto-mount on any element carrying the data attributes.
function autoMount() {
    document.querySelectorAll('[data-mail-builder]').forEach(el => {
        mountVue(el, 'builder', readOptions(el));
    });

    document.querySelectorAll('[data-mail-customizer-vue]').forEach(el => {
        mountVue(el, 'customizer', readOptions(el));
    });

    if (typeof window.React !== 'undefined' && typeof window.ReactDOM !== 'undefined') {
        document.querySelectorAll('[data-mail-customizer-react]').forEach(el => {
            const { templateId, variables } = readOptions(el);
            window.ReactDOM.render(
                window.React.createElement(MailCustomizerReact, { templateId, defaultVariables: variables }),
                el
            );
        });
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoMount);
    } else {
        autoMount();
    }
}

const base = '/api/mail-customizer';

export const api = {
    getTemplates: () => axios.get(`${base}/templates`),
    getTemplate: id => axios.get(`${base}/templates/${id}`),
    createTemplate: data => axios.post(`${base}/templates`, data),
    updateTemplate: (id, data) => axios.put(`${base}/templates/${id}`, data),
    deleteTemplate: id => axios.delete(`${base}/templates/${id}`),
    previewTemplate: (id, variables = {}) => axios.post(`${base}/templates/${id}/preview`, { variables }),
    previewDraft: data => axios.post(`${base}/templates/preview`, data),
    sendTestEmail: (id, data) => axios.post(`${base}/templates/${id}/send-test`, data),
    sendTestDraft: data => axios.post(`${base}/templates/send-test`, data),
    sendCustomEmail: (id, data) => axios.post(`${base}/templates/${id}/send-custom`, data),
    uploadImage: file => {
        const formData = new FormData();
        formData.append('image', file);
        return axios.post(`${base}/upload-image`, formData);
    },
    deleteImage: path => axios.delete(`${base}/delete-image`, { data: { path } })
};

export const events = {
    handlers: {},
    on(event, callback) {
        (this.handlers[event] = this.handlers[event] || []).push(callback);
    },
    off(event, callback) {
        if (this.handlers[event]) {
            this.handlers[event] = this.handlers[event].filter(cb => cb !== callback);
        }
    },
    emit(event, data) {
        (this.handlers[event] || []).forEach(callback => callback(data));
    }
};

// Handy globals for plain <script> usage (see the bundled Blade views).
if (typeof window !== 'undefined') {
    window.MailBuilderVue = MailBuilder;
    window.MailCustomizerVue = MailCustomizerVue;
    window.MailCustomizerReact = MailCustomizerReact;
}

export {
    BuilderToolbar,
    BuilderCanvas,
    PropertiesPanel,
    BuilderElement,
    MailBuilder,
    MailCustomizerVue,
    MailCustomizerReact,
    draggable
};
