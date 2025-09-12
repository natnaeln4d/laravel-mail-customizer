<template>
  <div 
    class="canvas-element"
    :class="{
      'selected': isSelected,
      'editable': element.editable,
      [`element-${element.type}`]: true
    }"
    @click.stop="selectElement"
  >
    <div class="element-content" v-html="renderedContent"></div>
    
    <div class="element-overlay" v-if="isSelected">
      <div class="element-handle drag-handle" title="Drag to reorder">
        <i class="fa fa-bars"></i>
      </div>
      
      <div class="element-actions">
        <button 
          class="btn-action btn-move-up"
          @click.stop="moveUp"
          :disabled="index === 0"
          title="Move up"
        >
          <i class="fa fa-arrow-up"></i>
        </button>
        
        <button 
          class="btn-action btn-move-down"
          @click.stop="moveDown"
          :disabled="isLastElement"
          title="Move down"
        >
          <i class="fa fa-arrow-down"></i>
        </button>
        
        <button 
          class="btn-action btn-remove"
          @click.stop="remove"
          title="Remove element"
        >
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>
    
    <div class="element-placeholder" v-if="!element.content && element.editable">
      <i class="fa fa-edit"></i>
      <span>Click to edit content</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BuilderElement',
  props: {
    element: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      required: true
    },
    isSelected: {
      type: Boolean,
      default: false
    },
    totalElements: {
      type: Number,
      default: 0
    }
  },
  computed: {
    renderedContent() {
      if (!this.element.content && this.element.defaultContent) {
        return this.element.defaultContent;
      }
      return this.element.content;
    },
    isLastElement() {
      return this.index === this.totalElements - 1;
    }
  },
  methods: {
    selectElement() {
      this.$emit('select', this.index);
    },
    remove() {
      this.$emit('remove', this.index);
    },
    moveUp() {
      this.$emit('move-up', this.index);
    },
    moveDown() {
      this.$emit('move-down', this.index);
    }
  }
}
</script>

<style scoped>
.canvas-element {
  position: relative;
  margin: 10px 0;
  padding: 15px;
  border: 2px solid transparent;
  border-radius: 6px;
  background: white;
  transition: all 0.2s ease;
  min-height: 50px;
}

.canvas-element:hover {
  border-color: #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.canvas-element.selected {
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
  z-index: 10;
}

.canvas-element.editable {
  cursor: pointer;
}

.canvas-element.editable:hover {
  background: #f8f9fa;
}

.element-content {
  min-height: 20px;
}

.element-content >>> img {
  max-width: 100%;
  height: auto;
}

.element-content >>> a {
  text-decoration: none;
}

.element-overlay {
  position: absolute;
  top: -12px;
  right: -12px;
  display: flex;
  gap: 4px;
}

.element-handle {
  background: #007bff;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  font-size: 12px;
}

.element-handle:active {
  cursor: grabbing;
}

.element-actions {
  display: flex;
  gap: 4px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 20px;
  padding: 2px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-action {
  width: 24px;
  height: 24px;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: #6c757d;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  transition: all 0.2s ease;
}

.btn-action:hover:not(:disabled) {
  background: #007bff;
  color: white;
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-remove:hover {
  background: #dc3545 !important;
}

.element-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 12px;
  min-height: 60px;
}

.element-placeholder i {
  font-size: 16px;
  margin-bottom: 4px;
}

.element-text {
  border-left: 3px solid #28a745;
}

.element-image {
  border-left: 3px solid #17a2b8;
}

.element-button {
  border-left: 3px solid #ffc107;
}

.element-divider {
  border-left: 3px solid #6c757d;
}

.element-spacer {
  border-left: 3px solid #6f42c1;
  background: repeating-linear-gradient(
    45deg,
    #f8f9fa,
    #f8f9fa 10px,
    #e9ecef 10px,
    #e9ecef 20px
  );
}
</style>