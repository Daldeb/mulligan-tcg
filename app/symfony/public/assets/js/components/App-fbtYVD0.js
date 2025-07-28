import { defineComponent, h } from 'vue'

export default defineComponent({
  name: 'App',
  render() {
    return h('div', { style: 'padding:2rem; font-family:sans-serif;' }, [
      h('h1', { style: 'color: green;' }, '✅ Symfony + Vue 3 + AssetMapper'),
      h('p', null, 'Bravo ! Le composant Vue est monté avec succès 🚀')
    ])
  }
})
