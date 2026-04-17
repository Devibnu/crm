import { defineConfig, mergeConfig } from 'vitest/config'
import viteConfig from './vite.config'

export default mergeConfig(viteConfig, defineConfig({
  test: {
    environment: 'jsdom',
    css: true,
    setupFiles: ['./src/tests/setup.ts'],
    include: ['./src/tests/**/*.spec.ts'],
    restoreMocks: true,
    clearMocks: true,
    server: {
      deps: {
        inline: ['vuetify'],
      },
    },
  },
}))