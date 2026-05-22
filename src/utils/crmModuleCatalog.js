const sharedMetrics = title => [
  { label: 'Workspace', value: title.split(' ')[0] || 'CRM' },
  { label: 'Status', value: 'Live' },
  { label: 'Navigation', value: 'Dynamic' },
];

export const moduleCatalog = {
  dashboards: {
    'service-management': {
      title: 'Service Management Dashboard',
      eyebrow: 'Dashboard',
      description: 'Monitor SLA health, case velocity, and omnichannel responsiveness from one executive surface.',
      accent: 'violet',
      highlights: ['Live service queues', 'Escalation heatmap', 'SLA pulse'],
      metrics: sharedMetrics('Service Management'),
    },
    'sales-enablement': {
      title: 'Sales Enablement Dashboard',
      eyebrow: 'Dashboard',
      description: 'Track lead quality, opportunity momentum, and pipeline confidence with a cleaner operational lens.',
      accent: 'indigo',
      highlights: ['Lead quality mix', 'Pipeline confidence', 'Deal momentum'],
      metrics: sharedMetrics('Sales Enablement'),
    },
    'marketing-automation': {
      title: 'Marketing Automation Dashboard',
      eyebrow: 'Dashboard',
      description: 'Review campaign velocity, consent health, and orchestration readiness in a focused marketing cockpit.',
      accent: 'pink',
      highlights: ['Campaign pulse', 'Consent readiness', 'Attribution clarity'],
      metrics: sharedMetrics('Marketing Automation'),
    },
    'customer-profile-360': {
      title: 'Customer Profile 360 Dashboard',
      eyebrow: 'Dashboard',
      description: 'Unify segmentation, interaction history, and preference signals in one premium customer intelligence view.',
      accent: 'cyan',
      highlights: ['Single customer lens', 'Preference signals', 'Journey recency'],
      metrics: sharedMetrics('Customer Profile 360'),
    },
  },
  marketing: {
    'campaign-management': {
      title: 'Campaign Management',
      eyebrow: 'Marketing Automation',
      description: 'Plan launch calendars, campaign ownership, and performance check-ins with a polished operating surface.',
      accent: 'pink',
      highlights: ['Campaign calendar', 'Channel mix', 'Owner handoff'],
      metrics: sharedMetrics('Campaign Management'),
    },
    'landing-page-form-builder': {
      title: 'Landing Page & Form Builder',
      eyebrow: 'Marketing Automation',
      description: 'Design capture experiences and conversion forms with reusable layouts and cleaner activation flow.',
      accent: 'violet',
      highlights: ['Reusable sections', 'Conversion flow', 'Lead capture'],
      metrics: sharedMetrics('Form Builder'),
    },
    'social-media-engagement': {
      title: 'Social Media Engagement',
      eyebrow: 'Marketing Automation',
      description: 'Track community responsiveness and content reactions with a single social engagement hub.',
      accent: 'indigo',
      highlights: ['Response queue', 'Community health', 'Content engagement'],
      metrics: sharedMetrics('Social Engagement'),
    },
    'customer-data-platform': {
      title: 'Customer Data Platform',
      eyebrow: 'Marketing Automation',
      description: 'Consolidate audience traits, signal quality, and activation readiness in a structured data workspace.',
      accent: 'cyan',
      highlights: ['Identity stitching', 'Segment health', 'Activation readiness'],
      metrics: sharedMetrics('CDP'),
    },
    'consent-management': {
      title: 'Consent Management',
      eyebrow: 'Marketing Automation',
      description: 'Review opt-in status, channel permissions, and governance posture without leaving the CRM shell.',
      accent: 'emerald',
      highlights: ['Opt-in inventory', 'Channel governance', 'Compliance view'],
      metrics: sharedMetrics('Consent Management'),
    },
    'marketing-analytics/campaign-performance': {
      title: 'Campaign Performance',
      eyebrow: 'Marketing Analytics',
      description: 'Compare reach, engagement, and conversion contribution across campaign waves and channels.',
      accent: 'violet',
      highlights: ['Performance trend', 'ROI snapshot', 'Channel rank'],
      metrics: sharedMetrics('Campaign Performance'),
    },
    'marketing-analytics/attribution-overview': {
      title: 'Attribution Overview',
      eyebrow: 'Marketing Analytics',
      description: 'Understand assisted touchpoints and channel influence with a clearer attribution narrative.',
      accent: 'pink',
      highlights: ['Touchpoint map', 'Influence scoring', 'Conversion assists'],
      metrics: sharedMetrics('Attribution Overview'),
    },
  },
  'customer-profile': {
    'customer-master': {
      title: 'Customer Master',
      eyebrow: 'Customer Profile 360',
      description: 'Centralize core customer records, ownership, and key lifecycle attributes in one master directory.',
      accent: 'cyan',
      highlights: ['Master records', 'Ownership', 'Lifecycle status'],
      metrics: sharedMetrics('Customer Master'),
    },
    'interaction-history': {
      title: 'Interaction History',
      eyebrow: 'Customer Profile 360',
      description: 'Review communication history, channel patterns, and recent engagement moments from one timeline.',
      accent: 'violet',
      highlights: ['Recent interactions', 'Channel mix', 'Follow-up recency'],
      metrics: sharedMetrics('Interaction History'),
    },
    segmentation: {
      title: 'Segmentation',
      eyebrow: 'Customer Profile 360',
      description: 'Organize audiences into action-ready segments with clearer rules and relationship context.',
      accent: 'indigo',
      highlights: ['Segment rules', 'Audience health', 'Activation fit'],
      metrics: sharedMetrics('Segmentation'),
    },
    'consent-preferences': {
      title: 'Consent & Preferences',
      eyebrow: 'Customer Profile 360',
      description: 'Manage communication permissions and profile-level preferences without leaving the customer lens.',
      accent: 'emerald',
      highlights: ['Preference center', 'Consent state', 'Channel fit'],
      metrics: sharedMetrics('Consent & Preferences'),
    },
    'customer-timeline': {
      title: 'Customer Timeline',
      eyebrow: 'Customer Profile 360',
      description: 'See the journey sequence from acquisition to support milestones in a cleaner chronological view.',
      accent: 'pink',
      highlights: ['Journey milestones', 'Context stitching', 'Lifecycle moments'],
      metrics: sharedMetrics('Customer Timeline'),
    },
  },
};

export const resolveCrmModule = (namespace, slug) => {
  const catalog = moduleCatalog[namespace] ?? {};

  return catalog[slug] ?? {
    title: slug.split('/').at(-1)?.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') ?? 'CRM Module',
    eyebrow: 'CRM Module',
    description: 'This module is ready for CRM expansion and already connected to the dynamic main menu architecture.',
    accent: 'violet',
    highlights: ['Dynamic routing', 'Seeder-driven menu', 'Responsive navigation'],
    metrics: sharedMetrics('CRM Module'),
  };
};
