<!-- src/Resources/assets/js/components/MailCustomizer.vue -->

<template>
  <div class="mail-customizer">
    <div class="row">
      <div class="col-md-8">
        <div class="form-group">
          <label>Template Name</label>
          <input type="text" v-model="template.name" class="form-control">
        </div>
        
        <div class="form-group">
          <label>Email Subject</label>
          <input type="text" v-model="template.subject" class="form-control">
        </div>
        
        <!-- Image Management Section -->
        <div class="card mb-4">
          <div class="card-header">
            <h5>Image Management</h5>
            <button type="button" class="btn btn-sm btn-primary" @click="addImage">
              Add Image
            </button>
          </div>
          <div class="card-body">
            <div v-for="(image, index) in template.images" :key="index" class="image-item mb-3 p-3 border rounded">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" v-model="image.url" class="form-control" placeholder="https://example.com/image.jpg">
                  </div>
                  
                  <div class="form-group">
                    <label>Image Type</label>
                    <select v-model="image.type" class="form-control" @change="onImageTypeChange(image)">
                      <option value="inline">Inline Image</option>
                      <option value="background">Background Image</option>
                    </select>
                  </div>
                  
                  <div class="form-group" v-if="image.type === 'inline'">
                    <label>Alt Text</label>
                    <input type="text" v-model="image.alt" class="form-control" placeholder="Image description">
                  </div>
                </div>
                
                <div class="col-md-6">
                  <div class="form-group" v-if="image.type === 'inline'">
                    <label>Shape</label>
                    <select v-model="image.shape" class="form-control">
                      <option value="rectangle">Rectangle</option>
                      <option value="rounded">Rounded Corners</option>
                      <option value="circle">Circle</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label>Position</label>
                    <select v-model="image.position" class="form-control">
                      <option value="left">Left</option>
                      <option value="right">Right</option>
                      <option value="center">Center</option>
                      <option value="top">Top</option>
                      <option value="bottom">Bottom</option>
                    </select>
                  </div>
                  
                  <div class="row" v-if="image.type === 'inline'">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Width</label>
                        <input type="text" v-model="image.width" class="form-control" placeholder="200px">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label>Height</label>
                        <input type="text" v-model="image.height" class="form-control" placeholder="auto">
                      </div>
                    </div>
                  </div>
                  
                  <div class="form-group" v-if="image.type === 'background'">
                    <label>Size</label>
                    <select v-model="image.size" class="form-control">
                      <option value="cover">Cover</option>
                      <option value="contain">Contain</option>
                      <option value="auto">Auto</option>
                    </select>
                  </div>
                  
                  <div class="form-group" v-if="image.type === 'background'">
                    <label>Repeat</label>
                    <select v-model="image.repeat" class="form-control">
                      <option value="no-repeat">No Repeat</option>
                      <option value="repeat">Repeat</option>
                      <option value="repeat-x">Repeat X</option>
                      <option value="repeat-y">Repeat Y</option>
                    </select>
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <small class="text-muted">Use <code>{{ getImagePlaceholder(image) }}</code> in your content</small>
                </div>
                <button type="button" class="btn btn-sm btn-danger" @click="removeImage(index)">
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label>Email Content</label>
          <textarea v-model="template.content" class="form-control" rows="10"></textarea>
          <small class="text-muted">Use image placeholders like {{image_1}} where you want images to appear</small>
        </div>
        
        <h4>Preview Variables</h4>
        <div class="row mb-3">
          <div class="col-md-6" v-for="(value, key) in variables" :key="key">
            <div class="form-group">
              <label>{{ key }}</label>
              <input type="text" v-model="variables[key]" class="form-control">
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <h4>Style Customization</h4>
        
        <div class="form-group">
          <label>Font Family</label>
          <select v-model="template.styles.body['font-family']" class="form-control">
            <option value="Arial, sans-serif">Arial</option>
            <option value="Helvetica, sans-serif">Helvetica</option>
            <option value="Georgia, serif">Georgia</option>
            <option value="'Times New Roman', serif">Times New Roman</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Font Size</label>
          <input type="text" v-model="template.styles.body['font-size']" class="form-control">
        </div>
        
        <div class="form-group">
          <label>Text Color</label>
          <input type="color" v-model="template.styles.body.color" class="form-control">
        </div>
        
        <div class="form-group">
          <label>Background Color</label>
          <input type="color" v-model="template.styles.body['background-color']" class="form-control">
        </div>
        
        <div class="form-group">
          <label>Layout</label>
          <select v-model="template.layout" class="form-control">
            <option value="default">Default</option>
            <option value="sidebar">Sidebar</option>
            <option value="centered">Centered</option>
          </select>
        </div>
        
        <button @click="preview" class="btn btn-info btn-block">Preview</button>
        <button @click="saveTemplate" class="btn btn-primary btn-block">Save Template</button>
        <button @click="sendTest" class="btn btn-warning btn-block">Send Test Email</button>
      </div>
    </div>
    
    <div v-if="showPreview" class="preview-modal">
      <div class="preview-content">
        <button class="close-preview" @click="showPreview = false">×</button>
        <h4>Email Preview</h4>
        <div v-html="previewContent"></div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    templateId: {
      type: Number,
      default: null
    },
    defaultVariables: {
      type: Object,
      default: () => ({ name: 'John Doe', email: 'john@example.com' })
    }
  },
  
  data() {
    return {
      template: {
        name: '',
        subject: '',
        content: '',
        images: [], // Added images array
        styles: {
          body: {
            'font-family': 'Arial, sans-serif',
            'font-size': '14px',
            color: '#333333',
            'background-color': '#ffffff'
          }
        },
        layout: 'default'
      },
      variables: {...this.defaultVariables},
      showPreview: false,
      previewContent: '',
      nextImageId: 1
    }
  },
  
  mounted() {
    if (this.templateId) {
      this.loadTemplate();
    }
  },
  
  methods: {
    async loadTemplate() {
      try {
        const response = await axios.get(`/api/mail-customizer/templates/${this.templateId}`);
        this.template = response.data;
        
        // Initialize images array if it doesn't exist
        if (!this.template.images) {
          this.template.images = [];
        }
        
        // Set next image ID
        if (this.template.images.length > 0) {
          this.nextImageId = Math.max(...this.template.images.map(img => img.id)) + 1;
        }
      } catch (error) {
        console.error('Error loading template:', error);
      }
    },
    
    addImage() {
      this.template.images.push({
        id: this.nextImageId++,
        type: 'inline',
        url: '',
        alt: '',
        shape: 'rectangle',
        position: 'left',
        width: '200px',
        height: 'auto',
        size: 'cover',
        repeat: 'no-repeat'
      });
    },
    
    removeImage(index) {
      this.template.images.splice(index, 1);
    },
    
    onImageTypeChange(image) {
      // Set defaults based on image type
      if (image.type === 'background') {
        image.position = 'center';
        image.size = 'cover';
        image.repeat = 'no-repeat';
      } else {
        image.position = 'left';
        image.shape = 'rectangle';
        image.width = '200px';
        image.height = 'auto';
      }
    },
    
    getImagePlaceholder(image) {
      return `{{image_${image.id}}}`;
    },
    
    async saveTemplate() {
      try {
        const url = this.templateId 
          ? `/api/mail-customizer/templates/${this.templateId}`
          : '/api/mail-customizer/templates';
          
        const method = this.templateId ? 'put' : 'post';
        
        const response = await axios[method](url, this.template);
        this.$emit('saved', response.data);
      } catch (error) {
        console.error('Error saving template:', error);
      }
    },
    
    async preview() {
      try {
        const response = await axios.post(`/api/mail-customizer/templates/${this.templateId}/preview`, this.variables);
        this.previewContent = response.data.preview;
        this.showPreview = true;
      } catch (error) {
        console.error('Error generating preview:', error);
      }
    },
    
    async sendTest() {
      try {
        const email = prompt('Enter email address for test:');
        if (email) {
          await axios.post(`/api/mail-customizer/templates/${this.templateId}/send-test`, {
            email,
            ...this.variables
          });
          alert('Test email sent successfully!');
        }
      } catch (error) {
        console.error('Error sending test email:', error);
      }
    }
  }
}
</script>

<style scoped>
.image-item {
  background-color: #f8f9fa;
}

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
  z-index: 1000;
}

.preview-content {
  background: white;
  padding: 20px;
  border-radius: 5px;
  max-width: 80%;
  max-height: 80%;
  overflow: auto;
  position: relative;
}

.close-preview {
  position: absolute;
  top: 10px;
  right: 10px;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}
</style>