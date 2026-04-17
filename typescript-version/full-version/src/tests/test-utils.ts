import { defineComponent, h, nextTick } from 'vue'
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import en from '@/plugins/i18n/locales/en.json'
import id from '@/plugins/i18n/locales/id.json'

const SimpleBlock = (name: string, tag = 'div') => defineComponent({
  name,
  inheritAttrs: false,
  props: {
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    modelValue: { type: Boolean, default: true },
    color: { type: String, default: '' },
  },
  emits: ['click', 'update:modelValue'],
  setup(props, { attrs, slots, emit }) {
    return () => h(tag, {
      ...attrs,
      'data-component': name,
      onClick: (event: Event) => emit('click', event),
    }, [
      props.title ? h('div', { class: `${name}-title` }, props.title) : null,
      props.subtitle ? h('div', { class: `${name}-subtitle` }, props.subtitle) : null,
      slots.prepend?.(),
      slots.append?.(),
      props.modelValue === false ? null : slots.default?.(),
    ])
  },
})

const stringifyValue = (value: unknown) => value === null ? '__null__' : String(value ?? '')

const AppSelectStub = defineComponent({
  name: 'AppSelect',
  inheritAttrs: false,
  props: {
    modelValue: { type: [String, Number, Boolean, Object], default: null },
    items: { type: Array as () => Array<{ title: string, value: unknown }>, default: () => [] },
    label: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () => h('label', { class: 'test-select', ...attrs }, [
      props.label ? h('span', props.label) : null,
      h('select', {
        value: stringifyValue(props.modelValue),
        onChange: (event: Event) => {
          const value = (event.target as HTMLSelectElement).value
          const matchedItem = props.items.find(item => stringifyValue(item.value) === value)

          emit('update:modelValue', value === '__null__' ? null : (matchedItem?.value ?? value))
        },
      }, props.items.map(item => h('option', { value: stringifyValue(item.value) }, item.title))),
    ])
  },
})

const AppTextFieldStub = defineComponent({
  name: 'AppTextField',
  inheritAttrs: false,
  props: {
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    type: { type: String, default: 'text' },
  },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () => h('label', { class: 'test-input', ...attrs }, [
      props.label ? h('span', props.label) : null,
      h('input', {
        type: props.type,
        value: props.modelValue as string | number,
        onInput: (event: Event) => {
          const value = (event.target as HTMLInputElement).value
          emit('update:modelValue', props.type === 'number' && value !== '' ? Number(value) : value)
        },
      }),
    ])
  },
})

const AppTextareaStub = defineComponent({
  name: 'AppTextarea',
  inheritAttrs: false,
  props: {
    modelValue: { type: String, default: '' },
    label: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () => h('label', { class: 'test-textarea', ...attrs }, [
      props.label ? h('span', props.label) : null,
      h('textarea', {
        value: props.modelValue,
        onInput: (event: Event) => emit('update:modelValue', (event.target as HTMLTextAreaElement).value),
      }),
    ])
  },
})

const TablePaginationStub = defineComponent({
  name: 'TablePagination',
  props: {
    page: { type: Number, default: 1 },
    itemsPerPage: { type: Number, default: 10 },
    totalItems: { type: Number, default: 0 },
  },
  setup(props) {
    return () => h('div', { class: 'test-pagination' }, `${props.page}/${props.itemsPerPage}/${props.totalItems}`)
  },
})

const VDataTableStub = defineComponent({
  name: 'VDataTable',
  props: {
    headers: { type: Array as () => Array<{ title: string, key: string }>, default: () => [] },
    items: { type: Array as () => Array<Record<string, unknown>>, default: () => [] },
  },
  setup(props, { slots }) {
    return () => h('div', { class: 'test-data-table' }, [
      h('table', [
        h('thead', [
          h('tr', props.headers.map(header => h('th', header.title))),
        ]),
        h('tbody', props.items.map(item => h('tr', { key: String(item.id ?? item.code ?? Math.random()) }, props.headers.map(header => {
          const slot = slots[`item.${header.key}`]

          return h('td', slot ? slot({ item }) : String(item[header.key] ?? ''))
        })))),
      ]),
      slots.bottom?.(),
    ])
  },
})

const VDialogStub = defineComponent({
  name: 'VDialog',
  props: {
    modelValue: { type: Boolean, default: false },
  },
  setup(props, { slots }) {
    return () => props.modelValue ? h('div', { class: 'test-dialog' }, slots.default?.()) : null
  },
})

export const createTestingI18n = (locale = 'id') => createI18n({
  legacy: false,
  locale,
  fallbackLocale: 'en',
  messages: { en, id },
})

export const mountSalesView = (component: any, locale = 'id'): VueWrapper => {
  const i18n = createTestingI18n(locale)

  return mount(component, {
    global: {
      plugins: [i18n],
      stubs: {
        VCard: SimpleBlock('VCard'),
        VCardText: SimpleBlock('VCardText'),
        VCardItem: SimpleBlock('VCardItem'),
        VRow: SimpleBlock('VRow'),
        VCol: SimpleBlock('VCol'),
        VBtn: SimpleBlock('VBtn', 'button'),
        VAlert: SimpleBlock('VAlert'),
        VChip: SimpleBlock('VChip', 'span'),
        VSnackbar: SimpleBlock('VSnackbar'),
        VDialog: VDialogStub,
        VDataTable: VDataTableStub,
        AppSelect: AppSelectStub,
        AppTextField: AppTextFieldStub,
        AppTextarea: AppTextareaStub,
        TablePagination: TablePaginationStub,
      },
    },
  })
}

export const flushUi = async () => {
  await flushPromises()
  await nextTick()
}