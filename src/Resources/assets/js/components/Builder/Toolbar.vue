<template>
  <div class="builder-toolbar">
    <h4>Elements</h4>
    <div class="toolbar-elements">
      <div 
        v-for="element in elements" 
        :key="element.type" 
        class="toolbar-element"
        draggable="true"
        @dragstart="onDragStart($event, element)"
        :title="element.label"
      >
        <i :class="['fa', element.icon]"></i>
        <span>{{ element.label }}</span>
      </div>
    </div>
    
    <div class="toolbar-actions mt-3">
      <button class="btn btn-sm btn-outline-primary" @click="clearCanvas">
        <i class="fa fa-trash"></i> Clear
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BuilderToolbar',
  props: {
    elements: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    onDragStart(event, element) {
      event.dataTransfer.setData('elementType', element.type);
      event.dataTransfer.effectAllowed = 'copy';
    },
    clearCanvas() {
      this.$emit('clear-canvas');
    }
  }
}
</script>

<style scoped>
.builder-toolbar {
  padding: 15px;
  background: #f8f9fa;
  border-right: 1px solid #dee2e6;
  height: 100%;
  overflow-y: auto;
}

.toolbar-elements {
  display: grid;
  gap: 8px;
}

.toolbar-element {
  padding: 12px;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  background: white;
  cursor: grab;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.toolbar-element:hover {
  border-color: #007bff;
  box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
  transform: translateY(-1px);
}

.toolbar-element:active {
  cursor: grabbing;
}

.toolbar-element i {
  width: 16px;
  text-align: center;
  color: #6c757d;
}

.toolbar-element span {
  font-size: 14px;
  color: #495057;
}

.toolbar-actions {
  border-top: 1px solid #dee2e6;
  padding-top: 15px;
}

.btn {
  width: 100%;
}
</style>