<!-- src/Resources/assets/js/components/MailBuilder.vue -->
<template>
  <div class="mail-builder">
    <div class="builder-header">
      <h4>Email Template Builder</h4>
      <div class="builder-actions">
        <button class="btn btn-sm btn-outline-primary" @click="saveTemplate">
          <i class="fa fa-save"></i> Save
        </button>
        <button class="btn btn-sm btn-outline-info" @click="previewTemplate">
          <i class="fa fa-eye"></i> Preview
        </button>
        <button class="btn btn-sm btn-outline-success" @click="sendTestEmail">
          <i class="fa fa-paper-plane"></i> Send Test
        </button>
      </div>
    </div>
    
    <div class="builder-content">
      <builder-toolbar 
        :elements="builderElements"
        @clear-canvas="clearCanvas"
        class="builder-sidebar"
      />
      
      <builder-canvas
        v-model="templateStructure"
        :selected-element-index="selectedElementIndex"
        @add-element="addElement"
        @select-element="selectElement"
        @remove-element="removeElement"
        @move-element-up="moveElementUp"
        @move-element-down="moveElementDown"
        @order-changed="onOrderChange"
        class="builder-main"
      />
      
      <properties-panel
        :selected-element="selectedElement"
        :selected-element-index="selectedElementIndex"
        @update-element="updateElement"
        @remove-element="removeElement"
        @upload-image="uploadImage"
        class="builder-sidebar"
      />
    </div>

    <!-- Preview Modal -->
    <div v-if="showPreview" class="preview-modal">
      <div class="preview-content">
        <button class="close-preview" @click="showPreview = false">
          <i class="fa fa-times"></i>
        </button>
        <h5>Email Preview</h5>
        <div class="preview-iframe-container">
          <iframe 
            :srcdoc="previewContent" 
            class="preview-iframe"
            sandbox="allow-same-origin"
          ></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { v4 as uuidv4 } from 'uuid';
import BuilderToolbar from './Builder/Toolbar.vue';
import BuilderCanvas from './Builder/Canvas.vue';
import PropertiesPanel from './Builder/PropertiesPanel.vue';

export default {
  name: 'MailBuilder',
  components: {
    BuilderToolbar,
    BuilderCanvas,
    PropertiesPanel
  },
  props: {
    templateId: Number,
    defaultVariables: Object
  },
  data() {
    return {
      builderElements: [],
      templateStructure: [],
      selectedElementIndex: null,
      showPreview: false,
      previewContent: '',
      loading: false
    };
  },
  computed: {
    selectedElement() {
      return this.selectedElementIndex !== null ? 
        this.templateStructure[this.selectedElementIndex] : null;
    }
  },
  async mounted() {
    await this.loadBuilderElements();
    if (this.templateId) {
      await this.loadTemplate();
    }
  },
  methods: {
    async loadBuilderElements() {
      try {
        // Try to load elements from API
        const response = await axios.get('/api/mail-customizer/builder/elements');
        this.builderElements = response.data.elements;
      } catch (error) {
        console.warn('API not available, using default elements:', error);
        // Fallback to default elements
        this.builderElements = this.getDefaultElements();
      }
    },
    
    getDefaultElements() {
      return [
        {
          type: 'text',
          label: 'Text',
          icon: 'fa-font',
          defaultContent: '<p style="margin: 0; padding: 10px 0;">Your text here</p>',
          editable: true,
          styles: {
            'font-family': 'Arial, sans-serif',
            'font-size': '14px',
            color: '#333333',
            padding: '10px',
            'background-color': '#ffffff'
          }
        },
        {
          type: 'image',
          label: 'Image',
          icon: 'fa-image',
          defaultContent: '<div style="text-align: center; padding: 10px 0;"><img src="https://via.placeholder.com/300x200" style="max-width: 100%; height: auto; border: 0;" alt="Placeholder image"></div>',
          editable: false,
          properties: {
            src: 'https://via.placeholder.com/300x200',
            alt: 'Placeholder image',
            alignment: 'center',
            width: '',
            height: ''
          }
        },
        {
          type: 'button',
          label: 'Button',
          icon: 'fa-square',
          defaultContent: '<div style="text-align: center; padding: 15px 0;"><a href="#" style="display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-family: Arial, sans-serif; font-size: 14px;">Click Me</a></div>',
          editable: true,
          properties: {
            text: 'Click Me',
            href: '#',
            'background-color': '#007bff',
            color: '#ffffff'
          },
          styles: {
            'text-align': 'center',
            padding: '15px 0'
          }
        },
        {
          type: 'divider',
          label: 'Divider',
          icon: 'fa-minus',
          defaultContent: '<div style="padding: 10px 0;"><hr style="border: none; border-top: 1px solid #cccccc; margin: 0;"></div>',
          editable: false
        },
        {
          type: 'spacer',
          label: 'Spacer',
          icon: 'fa-arrows-alt-v',
          defaultContent: '<div style="height: 20px;"></div>',
          editable: true,
          properties: {
            height: 20
          }
        }
      ];
    },
    
    async loadTemplate() {
      try {
        const response = await axios.get(`/api/mail-customizer/templates/${this.templateId}`);
        if (response.data.data && response.data.data.structure) {
          this.templateStructure = response.data.data.structure;
        } else if (response.data.structure) {
          this.templateStructure = response.data.structure;
        }
      } catch (error) {
        console.error('Error loading template:', error);
      }
    },
    
    addElement(elementType) {
      const elementConfig = this.builderElements.find(e => e.type === elementType);
      if (elementConfig) {
        const newElement = {
          id: uuidv4(),
          type: elementType,
          content: elementConfig.defaultContent,
          properties: { ...(elementConfig.properties || {}) },
          styles: { ...(elementConfig.styles || {}) },
          editable: elementConfig.editable
        };
        
        this.templateStructure.push(newElement);
        this.selectedElementIndex = this.templateStructure.length - 1;
      }
    },
    
    selectElement(index) {
      this.selectedElementIndex = index;
    },
    
    removeElement(index) {
      this.templateStructure.splice(index, 1);
      if (this.selectedElementIndex === index) {
        this.selectedElementIndex = null;
      } else if (this.selectedElementIndex > index) {
        this.selectedElementIndex--;
      }
    },
    
    updateElement(index, element) {
      this.templateStructure.splice(index, 1, { ...element });
    },
    
    moveElementUp(index) {
      if (index > 0) {
        const element = this.templateStructure.splice(index, 1)[0];
        this.templateStructure.splice(index - 1, 0, element);
        this.selectedElementIndex = index - 1;
      }
    },
    
    moveElementDown(index) {
      if (index < this.templateStructure.length - 1) {
        const element = this.templateStructure.splice(index, 1)[0];
        this.templateStructure.splice(index + 1, 0, element);
        this.selectedElementIndex = index + 1;
      }
    },
    
    onOrderChange() {
      // Handle order changes if needed
    },
    
    clearCanvas() {
      if (confirm('Are you sure you want to clear all elements?')) {
        this.templateStructure = [];
        this.selectedElementIndex = null;
      }
    },
    
    async uploadImage() {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.onchange = async (e) => {
        const file = e.target.files[0];
        if (file) {
          try {
            const formData = new FormData();
            formData.append('image', file);
            const response = await axios.post('/api/mail-customizer/upload-image', formData);
            
            if (this.selectedElement && this.selectedElement.type === 'image') {
              this.selectedElement.properties.src = response.data.url;
              this.updateElement(this.selectedElementIndex, this.selectedElement);
            }
          } catch (error) {
            console.error('Error uploading image:', error);
            alert('Error uploading image');
          }
        }
      };
      input.click();
    },
    
    async saveTemplate() {
      this.loading = true;
      try {
        const templateData = this.getTemplateData();
        const url = this.templateId 
          ? `/api/mail-customizer/templates/${this.templateId}`
          : '/api/mail-customizer/templates';
          
        const method = this.templateId ? 'put' : 'post';
        
        const name = prompt('Template name:', this.templateId ? 'Updated Template' : 'New Template');
        if (!name) return;
        
        const subject = prompt('Email subject:', 'New Email Template');
        if (!subject) return;
        
        const response = await axios[method](url, {
          name: name,
          subject: subject,
          content: templateData.content,
          structure: templateData.structure,
          styles: {
            body: {
              'font-family': 'Arial, sans-serif',
              'font-size': '14px',
              'color': '#333333',
              'background-color': '#ffffff',
              'margin': '0',
              'padding': '0'
            }
          },
          images: [],
          layout: 'default'
        });
        
        alert('Template saved successfully!');
        console.log('Template saved:', response.data);
        
      } catch (error) {
        console.error('Error saving template:', error);
        alert('Error saving template: ' + (error.response?.data?.message || error.message));
      } finally {
        this.loading = false;
      }
    },
    
    async previewTemplate() {
      this.loading = true;
      try {
        const templateData = this.getTemplateData();
        
        // Generate preview HTML
        const previewHtml = `
          <!DOCTYPE html>
          <html>
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
              body {
                font-family: Arial, sans-serif;
                font-size: 14px;
                color: #333333;
                background-color: #ffffff;
                margin: 0;
                padding: 20px;
              }
              .email-container {
                max-width: 600px;
                margin: 0 auto;
                background: #ffffff;
                border: 1px solid #dddddd;
              }
              .email-content {
                padding: 20px;
              }
            </style>
          </head>
          <body>
            <div class="email-container">
              <div class="email-content">
                ${templateData.content}
              </div>
            </div>
          </body>
          </html>
        `;
        
        this.previewContent = previewHtml;
        this.showPreview = true;
        
      } catch (error) {
        console.error('Error generating preview:', error);
        alert('Error generating preview');
      } finally {
        this.loading = false;
      }
    },
    
    async sendTestEmail() {
      const email = prompt('Enter email address for test:');
      if (!email) return;
      
      this.loading = true;
      try {
        const templateData = this.getTemplateData();
        
        const url = this.templateId
          ? `/api/mail-customizer/templates/${this.templateId}/send-test`
          : '/api/mail-customizer/templates/send-test';

        // Without a saved template we send the current draft from the canvas.
        await axios.post(url, {
          email: email,
          subject: 'Test email',
          content: templateData.content,
          structure: templateData.structure,
          variables: this.defaultVariables || {}
        });

        alert(`Test email sent to ${email}`);

      } catch (error) {
        console.error('Error sending test email:', error);
        alert('Error sending test email: ' + (error.response?.data?.message || error.message));
      } finally {
        this.loading = false;
      }
    },
    
    getTemplateData() {
      return {
        structure: this.templateStructure,
        content: this.generateHtmlContent()
      };
    },
    
    generateHtmlContent() {
      // Generate HTML from the template structure
      let html = '';
      
      this.templateStructure.forEach(element => {
        let elementHtml = element.content || element.defaultContent || '';
        
        // Apply styles
        if (element.styles && Object.keys(element.styles).length > 0) {
          const styleString = Object.entries(element.styles)
            .map(([key, value]) => `${key}: ${value};`)
            .join(' ');
          
          elementHtml = elementHtml.replace('<div', `<div style="${styleString}"`);
        }
        
        html += elementHtml;
      });
      
      return html || '<p>No content yet. Add elements from the toolbar.</p>';
    }
  }
};
</script>

<style scoped>
.mail-builder {
  display: flex;
  flex-direction: column;
  height: 80vh;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}

.builder-header {
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.builder-header h4 {
  margin: 0;
  color: #495057;
}

.builder-actions {
  display: flex;
  gap: 10px;
}

.builder-content {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.builder-sidebar {
  width: 250px;
  background: #f8f9fa;
  border-right: 1px solid #dee2e6;
  overflow-y: auto;
}

.builder-main {
  flex: 1;
  background: #ffffff;
  overflow-y: auto;
}

/* Preview Modal Styles */
.preview-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.preview-content {
  background: white;
  padding: 20px;
  border-radius: 8px;
  width: 80%;
  height: 80%;
  display: flex;
  flex-direction: column;
  position: relative;
}

.close-preview {
  position: absolute;
  top: 10px;
  right: 10px;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #6c757d;
}

.preview-iframe-container {
  flex: 1;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  overflow: hidden;
}

.preview-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
</style>