<script setup>
import { computed } from 'vue'

const props = defineProps({
  percentage: { type: Number, required: true },
  size: { type: Number, default: 168 },
  stroke: { type: Number, default: 14 },
})

const radius = computed(() => (props.size - props.stroke) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)
const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)))
const offset = computed(() => circumference.value * (1 - clamped.value / 100))
const tone = computed(() => (clamped.value >= 70 ? 'success' : clamped.value >= 40 ? 'primary' : 'danger'))
const display = computed(() => Number(clamped.value.toFixed(2)).toLocaleString('pt-BR'))
</script>

<template>
  <div class="ring" :class="tone" :style="{ width: `${size}px`, height: `${size}px` }" role="img" :aria-label="`Pontuação: ${display}%`">
    <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`" aria-hidden="true">
      <circle class="track" :cx="size / 2" :cy="size / 2" :r="radius" :stroke-width="stroke" fill="none" />
      <circle
        class="value"
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        :stroke-width="stroke"
        fill="none"
        stroke-linecap="round"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="offset"
        :transform="`rotate(-90 ${size / 2} ${size / 2})`"
      />
    </svg>
    <div class="label" aria-hidden="true">
      <span class="pct">{{ display }}<small>%</small></span>
    </div>
  </div>
</template>

<style scoped>
.ring { position: relative; display: inline-grid; place-items: center; }
.ring svg { position: absolute; inset: 0; }
.track { stroke: var(--color-surface-2); }
.value { stroke: var(--color-primary); transition: stroke-dashoffset 700ms var(--ease); }
.success .value { stroke: var(--color-success); }
.danger .value { stroke: var(--color-danger); }
.label { position: relative; text-align: center; }
.pct { font-size: 2.5rem; font-weight: 800; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
.pct small { font-size: 1.25rem; font-weight: 700; color: var(--color-text-muted); margin-left: 2px; }
</style>
