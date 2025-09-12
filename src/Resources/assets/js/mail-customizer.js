import Vue from 'vue';
import draggable from 'vuedraggable';
import BuilderToolbar from './components/Builder/Toolbar.vue';
import BuilderCanvas from './components/Builder/Canvas.vue';
import PropertiesPanel from './components/Builder/PropertiesPanel.vue';
import BuilderElement from './components/Builder/Element.vue';
import MailBuilder from './components/MailBuilder.vue';

Vue.component('builder-toolbar', BuilderToolbar);
Vue.component('builder-canvas', BuilderCanvas);
Vue.component('properties-panel', PropertiesPanel);
Vue.component('builder-element', BuilderElement);
Vue.component('mail-builder', MailBuilder);
Vue.component('draggable', draggable);

export { BuilderToolbar, BuilderCanvas, PropertiesPanel, BuilderElement, MailBuilder, draggable };





Vue.component('mail-customizer', MailCustomizerVue);


export { MailCustomizerVue, MailCustomizerReact };


document.addEventListener('DOMContentLoaded', function() {
    const vueApps = document.querySelectorAll('[data-mail-customizer-vue]');

    const builderElements = document.querySelectorAll('[data-mail-builder]');
    
    builderElements.forEach(element => {
        const templateId = element.getAttribute('data-template-id');
        const variablesJson = element.getAttribute('data-variables');
        
        let variables = {
            name: 'Guest User',
            email: 'user@example.com'
        };
        
        try {
            if (variablesJson) {
                variables = JSON.parse(variablesJson);
            }
        } catch (e) {
            console.error('Failed to parse variables JSON:', e);
        }
        
        new Vue({
            el: element,
            data: {
                templateId: templateId ? parseInt(templateId) : null,
                defaultVariables: variables
            },
            template: '<mail-builder :template-id="templateId" :default-variables="defaultVariables"></mail-builder>'
        });
    });
    
    vueApps.forEach(appElement => {
        const templateId = appElement.getAttribute('data-template-id');
        const variablesJson = appElement.getAttribute('data-variables');
        
        let variables = {
            name: 'Guest User',
            email: 'user@example.com'
        };
        
        try {
            if (variablesJson) {
                variables = JSON.parse(variablesJson);
            }
        } catch (e) {
            console.error('Failed to parse variables JSON:', e);
        }
        
        new Vue({
            el: appElement,
            data: {
                templateId: templateId ? parseInt(templateId) : null,
                defaultVariables: variables
            }
        });
    });

    if (typeof window.React !== 'undefined') {
        const reactApps = document.querySelectorAll('[data-mail-customizer-react]');
        
        reactApps.forEach(appElement => {
            const templateId = appElement.getAttribute('data-template-id');
            const variablesJson = appElement.getAttribute('data-variables');
            
            let variables = {
                name: 'Guest User',
                email: 'user@example.com'
            };
            
            try {
                if (variablesJson) {
                    variables = JSON.parse(variablesJson);
                }
            } catch (e) {
                console.error('Failed to parse variables JSON:', e);
            }

            const { createElement } = window.React;
            const { render } = window.ReactDOM;
            
            const ReactComponent = createElement(MailCustomizerReact, {
                templateId: templateId ? parseInt(templateId) : null,
                defaultVariables: variables
            });
            
            render(ReactComponent, appElement);
        });
    }
});

window.MailCustomizer = {
       initVue: function(elementId, options = {}) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error('Element not found:', elementId);
            return null;
        }
        
        return new Vue({
            el: '#' + elementId,
            data: {
                templateId: options.templateId || null,
                defaultVariables: options.variables || {
                    name: 'Guest User',
                    email: 'user@example.com'
                }
            },
            template: '<mail-builder :template-id="templateId" :default-variables="defaultVariables"></mail-builder>'
        });
    },
    

    initReact: function(elementId, options = {}) {
        if (typeof window.React === 'undefined' || typeof window.ReactDOM === 'undefined') {
            console.error('React and ReactDOM must be loaded before initializing MailCustomizer React component');
            return null;
        }
        
        const element = document.getElementById(elementId);
        if (!element) return null;
        
        const { createElement } = window.React;
        const { render } = window.ReactDOM;
        
        const ReactComponent = createElement(MailCustomizerReact, {
            templateId: options.templateId || null,
            defaultVariables: options.variables || {
                name: 'Guest User',
                email: 'user@example.com'
            }
        });
        
        return render(ReactComponent, element);
    },

    api: {
        getTemplates: function() {
            return axios.get('/api/mail-customizer/templates');
        },
        
        getTemplate: function(id) {
            return axios.get(`/api/mail-customizer/templates/${id}`);
        },
        
        createTemplate: function(data) {
            return axios.post('/api/mail-customizer/templates', data);
        },
        
        updateTemplate: function(id, data) {
            return axios.put(`/api/mail-customizer/templates/${id}`, data);
        },
        
        deleteTemplate: function(id) {
            return axios.delete(`/api/mail-customizer/templates/${id}`);
        },
        
        previewTemplate: function(id, data) {
            return axios.post(`/api/mail-customizer/templates/${id}/preview`, data);
        },
        
        sendTestEmail: function(id, data) {
            return axios.post(`/api/mail-customizer/templates/${id}/send-test`, data);
        },
        
        sendCustomEmail: function(id, data) {
            return axios.post(`/api/mail-customizer/templates/${id}/send-custom`, data);
        },
        
        uploadImage: function(file) {
            const formData = new FormData();
            formData.append('image', file);
            return axios.post('/api/mail-customizer/upload-image', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
        },
        
        deleteImage: function(path) {
            return axios.delete('/api/mail-customizer/delete-image', {
                data: { path }
            });
        }
    },
    

    utils: {
        compileTemplate: function(template, data) {
            let compiled = template.content;
            for (const [key, value] of Object.entries(data)) {
                compiled = compiled.replace(new RegExp(`{{${key}}}`, 'g'), value);
                compiled = compiled.replace(new RegExp(`{{ ${key} }}`, 'g'), value);
            }
            return compiled;
        },
        
        generateImagePlaceholder: function(imageId) {
            return `{{image_${imageId}}}`;
        },
        
        extractImagePlaceholders: function(content) {
            return content.match(/{{image_\d+}}/g) || [];
        }
    }
};


window.MailCustomizerEvents = {
    events: {},
    
    on: function(event, callback) {
        if (!this.events[event]) this.events[event] = [];
        this.events[event].push(callback);
    },
    
    off: function(event, callback) {
        if (!this.events[event]) return;
        this.events[event] = this.events[event].filter(cb => cb !== callback);
    },
    
    emit: function(event, data) {
        if (!this.events[event]) return;
        this.events[event].forEach(callback => callback(data));
    }
};


if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.MailCustomizer.init();
    });
} else {
    window.MailCustomizer.init();
}


if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MailCustomizerVue,
        MailCustomizerReact,
        MailCustomizer: window.MailCustomizer,
        MailCustomizerEvents: window.MailCustomizerEvents
    };
}