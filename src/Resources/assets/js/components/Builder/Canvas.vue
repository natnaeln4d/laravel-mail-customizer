
<template>
  <div 
    class="builder-canvas"
    @dragover.prevent
    @drop="onDrop"
    @click="deselectElement"
  >
    <div class="canvas-header">
      <h5>Email Canvas</h5>
      <div class="canvas-actions">
        <button 
          class="btn btn-sm btn-outline-secondary" 
          @click="toggleMobilePreview"
          title="Toggle mobile preview"
        >
          <i class="fa" :class="mobileView ? 'fa-desktop' : 'fa-mobile'"></i>
        </button>
      </div>
    </div>
    
    <div 
      class="canvas-content"
      :class="{ 'mobile-view': mobileView }"
    >
      <div 
        v-if="elements.length === 0" 
        class="empty-canvas"
      >
        <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
        <p>Drag elements from the toolbar to start building</p>
      </div>
      
      <draggable 
        v-model="elements"
        v-bind="dragOptions"
        @start="onDragStart"
        @end="onDragEnd"
        @change="onOrderChange"
      >
        <transition-group name="element-list" tag="div" class="elements-container">
          <Element
            v-for="(element, index) in elements"
            :key="element.id"
            :element="element"
            :index="index"
            :is-selected="selectedElementIndex === index"
            @select="selectElement"
            @remove="removeElement"
            @move-up="moveElementUp"
            @move-down="moveElementDown"
          />
        </transition-group>
      </draggable>
    </div>
  </div>
</template>

<script>
import draggable from 'vuedraggable';
import Element from './Element.vue';

export default {
  name: 'BuilderCanvas',
  components: {
    draggable,
    Element
  },
  props: {
    value: {
      type: Array,
      default: () => []
    },
    selectedElementIndex: {
      type: Number,
      default: null
    }
  },
  data() {
    return {
      mobileView: false,
      isDragging: false
    };
  },
  computed: {
    elements: {
      get() {
        return this.value;
      },
      set(value) {
        this.$emit('input', value);
      }
    },
    dragOptions() {
      return {
        animation: 200,
        ghostClass: 'ghost-element',
        chosenClass: 'chosen-element',
        dragClass: 'dragging-element',
        handle: '.drag-handle'
      };
    }
  },
  methods: {
    onDrop(event) {
      const elementType = event.dataTransfer.getData('elementType');
      if (elementType) {
        this.$emit('add-element', elementType);
      }
    },
    selectElement(index) {
      this.$emit('select-element', index);
    },
    deselectElement(event) {
      // Only deselect if clicking on canvas background, not on an element
      if (event.target === this.$el || event.target.classList.contains('canvas-content')) {
        this.$emit('select-element', null);
      }
    },
    removeElement(index) {
      this.$emit('remove-element', index);
    },
    moveElementUp(index) {
      this.$emit('move-element-up', index);
    },
    moveElementDown(index) {
      this.$emit('move-element-down', index);
    },
    onDragStart() {
      this.isDragging = true;
    },
    onDragEnd() {
      this.isDragging = false;
    },
    onOrderChange() {
      this.$emit('order-changed');
    },
    toggleMobilePreview() {
      this.mobileView = !this.mobileView;
    }
  }
}
</script>

<style scoped>
.builder-canvas {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #fafafa;
}

.canvas-header {
  padding: 15px;
  background: white;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.canvas-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  background: white;
  margin: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.canvas-content.mobile-view {
  max-width: 375px;
  margin: 20px auto;
  min-height: 667px;
}

.empty-canvas {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 300px;
  color: #6c757d;
  text-align: center;
}

.elements-container {
  min-height: 100px;
}


.element-list-enter-active,
.element-list-leave-active {
  transition: all 0.3s ease;
}

.element-list-enter,
.element-list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.element-list-move {
  transition: transform 0.3s ease;
}


.ghost-element {
  opacity: 0.5;
  background: #c8ebfb;
}

.chosen-element {
  background: #e3f2fd !important;
}

.dragging-element {
  opacity: 0.8;
  transform: rotate(5deg);
}
</style>