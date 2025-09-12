<template>
  <div class="properties-panel">
    <div class="panel-header">
      <h5>Properties</h5>
      <button 
        v-if="selectedElement" 
        class="btn btn-sm btn-outline-danger"
        @click="removeSelectedElement"
        title="Remove element"
      >
        <i class="fa fa-trash"></i>
      </button>
    </div>
    
    <div class="panel-content">
      <div v-if="!selectedElement" class="no-selection">
        <i class="fa fa-mouse-pointer fa-2x text-muted mb-3"></i>
        <p>Select an element to edit its properties</p>
      </div>
      
      <div v-else class="element-properties">
        <div class="property-group">
          <h6>General</h6>
          <div class="property-item">
            <label>Element Type</label>
            <div class="form-control-static">{{ selectedElement.type }}</div>
          </div>
        </div>
        <div v-if="selectedElement.editable" class="property-group">
          <h6>Content</h6>
          <div class="property-item">
            <label>Text Content</label>
            <textarea 
              v-model="selectedElement.content" 
              class="form-control"
              rows="4"
              @input="updateElement"
              placeholder="Enter your content here..."
            ></textarea>
          </div>
        </div>

        <div v-if="selectedElement.type === 'image'" class="property-group">
          <h6>Image Settings</h6>
          <div class="property-item">
            <label>Image URL</label>
            <div class="input-group">
              <input 
                type="text" 
                v-model="selectedElement.properties.src" 
                class="form-control"
                @input="updateElement"
                placeholder="Enter image URL or upload"
              >
              <button 
                class="btn btn-outline-secondary"
                @click="uploadImage"
                title="Upload image"
              >
                <i class="fa fa-upload"></i>
              </button>
            </div>
          </div>
          
          <div class="property-item">
            <label>Alt Text</label>
            <input 
              type="text" 
              v-model="selectedElement.properties.alt" 
              class="form-control"
              @input="updateElement"
              placeholder="Alternative text"
            >
          </div>
          
          <div class="property-item">
            <label>Alignment</label>
            <select 
              v-model="selectedElement.properties.alignment" 
              class="form-select"
              @change="updateElement"
            >
              <option value="left">Left</option>
              <option value="center">Center</option>
              <option value="right">Right</option>
            </select>
          </div>
          
          <div class="property-item row">
            <div class="col">
              <label>Width</label>
              <input 
                type="number" 
                v-model="selectedElement.properties.width" 
                class="form-control"
                @input="updateElement"
                placeholder="px"
              >
            </div>
            <div class="col">
              <label>Height</label>
              <input 
                type="number" 
                v-model="selectedElement.properties.height" 
                class="form-control"
                @input="updateElement"
                placeholder="px"
              >
            </div>
          </div>
        </div>

        <!-- Spacer Properties -->
        <div v-if="selectedElement.type === 'spacer'" class="property-group">
          <h6>Spacer Settings</h6>
          <div class="property-item">
            <label>Height</label>
            <div class="input-group">
              <input 
                type="number" 
                v-model="selectedElement.properties.height" 
                class="form-control"
                @input="updateElement"
                min="1"
                max="500"
              >
              <span class="input-group-text">px</span>
            </div>
          </div>
        </div>

        <!-- Button Properties -->
        <div v-if="selectedElement.type === 'button'" class="property-group">
          <h6>Button Settings</h6>
          <div class="property-item">
            <label>Button Text</label>
            <input 
              type="text" 
              v-model="selectedElement.properties.text" 
              class="form-control"
              @input="updateElement"
              placeholder="Button text"
            >
          </div>
          
          <div class="property-item">
            <label>Link URL</label>
            <input 
              type="url" 
              v-model="selectedElement.properties.href" 
              class="form-control"
              @input="updateElement"
              placeholder="https://example.com"
            >
          </div>
          
          <div class="property-item">
            <label>Background Color</label>
            <input 
              type="color" 
              v-model="selectedElement.styles['background-color']" 
              class="form-control"
              @input="updateElement"
            >
          </div>
          
          <div class="property-item">
            <label>Text Color</label>
            <input 
              type="color" 
              v-model="selectedElement.styles.color" 
              class="form-control"
              @input="updateElement"
            >
          </div>
        </div>

        <!-- Style Properties -->
        <div class="property-group">
          <h6>Styles</h6>
          <div class="property-item" v-for="(value, property) in selectedElement.styles" :key="property">
            <label>{{ formatStyleLabel(property) }}</label>
            <input 
              type="text" 
              v-model="selectedElement.styles[property]" 
              class="form-control"
              @input="updateElement"
              :placeholder="property"
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PropertiesPanel',
  props: {
    selectedElement: {
      type: Object,
      default: null
    },
    selectedElementIndex: {
      type: Number,
      default: null
    }
  },
  methods: {
    updateElement() {
      this.$emit('update-element', this.selectedElementIndex, this.selectedElement);
    },
    removeSelectedElement() {
      if (this.selectedElementIndex !== null) {
        this.$emit('remove-element', this.selectedElementIndex);
      }
    },
    async uploadImage() {
      this.$emit('upload-image');
    },
    formatStyleLabel(property) {
      return property
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
    }
  },
  watch: {
    selectedElement: {
      handler() {
        if (this.selectedElement) {
          this.$emit('update-element', this.selectedElementIndex, this.selectedElement);
        }
      },
      deep: true
    }
  }
}
</script>

<style scoped>
.properties-panel {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
  border-left: 1px solid #dee2e6;
}

.panel-header {
  padding: 15px;
  background: white;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-content {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
}

.no-selection {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #6c757d;
  text-align: center;
}

.property-group {
  margin-bottom: 20px;
}

.property-group h6 {
  color: #495057;
  font-weight: 600;
  margin-bottom: 12px;
  padding-bottom: 5px;
  border-bottom: 1px solid #dee2e6;
}

.property-item {
  margin-bottom: 12px;
}

.property-item label {
  font-size: 12px;
  font-weight: 600;
  color: #495057;
  margin-bottom: 4px;
  display: block;
}

.form-control-static {
  padding: 6px 12px;
  background: #e9ecef;
  border-radius: 4px;
  font-size: 14px;
  color: #495057;
}

.input-group-text {
  font-size: 14px;
}

input[type="color"] {
  height: 38px;
  padding: 2px;
}

.panel-content::-webkit-scrollbar {
  width: 6px;
}

.panel-content::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.panel-content::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.panel-content::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>