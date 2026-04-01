import { createApp } from 'vue'
import SolarExplorer from './components/SolarExplorer.vue'

const imagesToPreload = [
    'https://img.freepik.com/free-photo/beautiful_1203-2633.jpg',
    'https://img.freepik.com/free-vector/pink-grid-neon-patterned-background_53876-114920.jpg',
    'https://img.freepik.com/free-photo/fireman-fire-fighting...',
  ]
  
  imagesToPreload.forEach(src => { new Image().src = src })
  
  createApp(SolarExplorer).mount('#solar-app')