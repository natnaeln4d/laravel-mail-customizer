<template>
  <div class="mail-builder">
    <BuilderToolbar 
      :elements="builderElements"
      @clear-canvas="clearCanvas"
    />
    
    <BuilderCanvas
      v-model="templateStructure"
      :selected-element-index="selectedElementIndex"
      @add-element="addElement"
      @select-element="selectElement"
      @remove-element="removeElement"
      @move-element-up="moveElementUp"
      @move-element-down="moveElementDown"
      @order-changed="onOrderChange"
    />
    
    <PropertiesPanel
      :selected-element="selectedElement"
      :selected-element-index="selectedElementIndex"
      @update-element="updateElement"
      @remove-element="removeElement"
      @upload-image="uploadImage"
    />
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
      selectedElementIndex: null
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
        const response = await axios.get('/api/mail-customizer/builder/elements');
        this.builderElements = response.data.elements;
      } catch (error) {
        console.error('Error loading builder elements:', error);
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
          defaultContent: '<p>Your text here</p>',
          editable: true,
          styles: {
            'font-family': 'Arial, sans-serif',
            'font-size': '14px',
            color: '#333333',
            padding: '10px'
          }
        },
        {
          type: 'image',
          label: 'Image',
          icon: 'fa-image',
          defaultContent: '<img src="{{image_placeholder}}" style="max-width: 100%; height: auto;" />',
          editable: false,
          properties: {
            src: '',
            alt: '',
            alignment: 'center',
            width: '',
            height: ''
          }
        },
        {
          type: 'button',
          label: 'Button',
          icon: 'fa-square',
          defaultContent: '<a href="#" style="display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; text-align: center;">Click Me</a>',
          editable: true,
          properties: {
            text: 'Click Me',
            href: '#'
          },
          styles: {
            'background-color': '#007bff',
            color: '#ffffff',
            'text-align': 'center',
            padding: '15px 0'
          }
        },
        {
          type: 'divider',
          label: 'Divider',
          icon: 'fa-minus',
          defaultContent: '<hr style="border: none; border-top: 1px solid #cccccc; margin: 20px 0;" />',
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
        if (response.data.structure) {
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
          properties: { ...elementConfig.properties },
          styles: { ...elementConfig.styles },
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
      this.templateStructure.splice(index, 1, element);
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
    
    getTemplateData() {
      return {
        structure: this.templateStructure,
        content: this.generateHtmlContent()
      };
    },
    
    generateHtmlContent() {

      return this.templateStructure.map(e => e.content).join('');
    }
  }
};
</script>

<style scoped>
.mail-builder {
  display: grid;
  grid-template-columns: 200px 1fr 300px;
  height: 600px;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}
</style>