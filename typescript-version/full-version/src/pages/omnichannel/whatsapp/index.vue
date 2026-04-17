<script setup lang="ts">
import { whatsappTemplateCatalog } from '@/data/whatsapp-template-catalog'

const router = useRouter()
const route = useRoute()

const moduleHealth = [
  {
    title: 'Inbox',
    description: 'Percakapan admin, penugasan penanggung jawab, dan mapping pelanggan ke manajemen tiket.',
    status: 'Next Build',
    color: 'success',
    to: '/omnichannel/whatsapp/inbox',
  },
  {
    title: 'Broadcast',
    description: 'Audience builder sudah aktif dan memakai customer dengan nomor WA yang tersedia.',
    status: 'Live V1',
    color: 'primary',
    to: '/marketing-automation/campaign-execution/whatsapp-broadcast',
  },
  {
    title: 'Templates',
    description: 'Template library resmi untuk dipakai ulang di broadcast dan inbox follow-up.',
    status: 'Live V1',
    color: 'info',
    to: '/marketing-automation/campaign-execution/whatsapp-templates',
  },
]

const overviewCards = computed(() => [
  {
    title: 'Approved Templates',
    value: whatsappTemplateCatalog.filter(template => template.status === 'approved').length,
    subtitle: 'Template yang sudah siap dipakai di workspace',
    color: 'info',
    icon: 'tabler-template',
  },
  {
    title: 'Broadcast-ready',
    value: whatsappTemplateCatalog.filter(template => template.channels.includes('broadcast')).length,
    subtitle: 'Template yang bisa langsung dipakai campaign',
    color: 'primary',
    icon: 'tabler-send',
  },
])

const featuredTemplates = computed(() => whatsappTemplateCatalog.slice(0, 3))

const openMenu = (to: string) => {
  router.push(to)
}

const submenuItems = [
  {
    title: 'Inbox',
    description: 'Percakapan admin',
    icon: 'tabler-message-circle-2',
    color: 'success',
    to: '/omnichannel/whatsapp/inbox',
  },
  {
    title: 'Broadcast',
    description: 'Campaign audience',
    icon: 'tabler-send',
    color: 'primary',
    to: '/marketing-automation/campaign-execution/whatsapp-broadcast',
  },
  {
    title: 'Templates',
    description: 'Library pesan',
    icon: 'tabler-template',
    color: 'info',
    to: '/marketing-automation/campaign-execution/whatsapp-templates',
  },
]

const isSubmenuActive = (to: string) => route.path === to
</script>

<template>
  <section class="d-flex flex-column gap-6">
    <VCard>
      <VCardText class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-4">
        <div>
          <div class="text-overline mb-2">Omnichannel / WhatsApp</div>
          <h4 class="text-h4 mb-1">WhatsApp Workspace</h4>
          <p class="mb-0 text-body-1">
            Parent page ini sekarang dipakai sebagai ringkasan operasional WhatsApp. Submenu tetap ada di sidebar, tetapi overview ini merangkum modul yang sudah hidup dan yang masih disiapkan.
          </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <VChip color="success" variant="tonal">Customer-linked Channel</VChip>
          <VChip color="info" variant="tonal">Template Library Active</VChip>
        </div>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText class="d-flex flex-column gap-4">
        <div class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-3">
          <div>
            <div class="text-h6">WhatsApp Submenu</div>
            <div class="text-body-2 text-medium-emphasis">Saat parent WhatsApp dibuka, submenu ini tetap tampil di layar supaya akses ke tiap workspace lebih jelas.</div>
          </div>

          <VChip color="primary" variant="tonal">3 Workspace Paths</VChip>
        </div>

        <VRow>
          <VCol v-for="item in submenuItems" :key="item.title" cols="12" sm="6" lg="3">
            <VCard
              class="cursor-pointer h-100"
              :color="isSubmenuActive(item.to) ? item.color : undefined"
              :variant="isSubmenuActive(item.to) ? 'flat' : 'outlined'"
              @click="openMenu(item.to)"
            >
              <VCardText class="d-flex align-center gap-3">
                <VAvatar :color="isSubmenuActive(item.to) ? 'white' : item.color" :variant="isSubmenuActive(item.to) ? 'flat' : 'tonal'" rounded size="42">
                  <VIcon :icon="item.icon" />
                </VAvatar>

                <div>
                  <div class="text-body-1 mb-1" :class="isSubmenuActive(item.to) ? 'text-white' : 'text-high-emphasis'">{{ item.title }}</div>
                  <div class="text-body-2" :class="isSubmenuActive(item.to) ? 'text-white opacity-80' : 'text-medium-emphasis'">{{ item.description }}</div>
                </div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VRow>
      <VCol v-for="card in overviewCards" :key="card.title" cols="12" md="6">
        <VCard>
          <VCardText class="d-flex align-start justify-space-between gap-3">
            <div>
              <div class="text-body-1 text-high-emphasis mb-1">{{ card.title }}</div>
              <h4 class="text-h4 mb-1">{{ card.value }}</h4>
              <div class="text-body-2 text-medium-emphasis">{{ card.subtitle }}</div>
            </div>

            <VAvatar :color="card.color" variant="tonal" rounded size="46">
              <VIcon :icon="card.icon" />
            </VAvatar>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow>
      <VCol cols="12" lg="7">
        <VCard>
          <VCardText class="d-flex flex-column gap-4">
            <div class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-3">
              <div>
                <div class="text-h6">Module Readiness</div>
                <div class="text-body-2 text-medium-emphasis">Sidebar submenu tetap jadi navigasi utama. Di sini status tiap modul diringkas supaya parent page punya fungsi yang lebih jelas.</div>
              </div>

              <VBtn color="primary" variant="tonal" prepend-icon="tabler-send" @click="openMenu('/marketing-automation/campaign-execution/whatsapp-broadcast')">
                Buka Broadcast
              </VBtn>
            </div>

            <div class="d-flex flex-column gap-3">
              <VCard
                v-for="item in moduleHealth"
                :key="item.title"
                class="cursor-pointer"
                variant="outlined"
                @click="openMenu(item.to)"
              >
                <VCardText class="d-flex flex-column flex-md-row align-md-center justify-space-between gap-3">
                  <div>
                    <div class="text-body-1 text-high-emphasis mb-1">{{ item.title }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ item.description }}</div>
                  </div>

                  <div class="d-flex align-center gap-3">
                    <VChip :color="item.color" variant="tonal">{{ item.status }}</VChip>
                    <VIcon icon="tabler-chevron-right" />
                  </div>
                </VCardText>
              </VCard>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <VCol cols="12" lg="5">
        <VCard>
          <VCardText class="d-flex flex-column gap-4">
            <div>
              <div class="text-h6">Template Highlights</div>
              <div class="text-body-2 text-medium-emphasis">Template menjadi fondasi utama untuk Broadcast dan follow-up dari workspace WhatsApp.</div>
            </div>

            <div class="d-flex flex-column gap-3">
              <div v-for="template in featuredTemplates" :key="template.id" class="workspace-template-item rounded border-sm pa-4">
                <div class="d-flex align-center justify-space-between gap-3 mb-2">
                  <div class="text-body-1 text-high-emphasis">{{ template.name }}</div>
                  <VChip size="small" :color="template.status === 'approved' ? 'success' : template.status === 'draft' ? 'warning' : 'secondary'" variant="tonal">
                    {{ template.status }}
                  </VChip>
                </div>

                <div class="text-body-2 text-medium-emphasis mb-2">{{ template.body }}</div>
                <div class="d-flex flex-wrap gap-2">
                  <VChip size="small" color="primary" variant="tonal">{{ template.category }}</VChip>
                  <VChip v-for="channel in template.channels.filter(channel => channel !== 'flow')" :key="channel" size="small" variant="outlined">{{ channel }}</VChip>
                </div>
              </div>
            </div>

            <VBtn block color="info" prepend-icon="tabler-template" @click="openMenu('/marketing-automation/campaign-execution/whatsapp-templates')">
              Kelola Templates
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
.workspace-template-item {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
