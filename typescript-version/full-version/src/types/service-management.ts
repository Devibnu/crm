export type ServiceTicketCategory = 'general' | 'technical' | 'billing' | 'priority-follow-up'
export type ServiceTicketPriority = 'low' | 'medium' | 'high' | 'critical'
export type ServiceTicketStatus = 'open' | 'in_progress' | 'resolved'
export type ServiceTicketAlertState = 'on_track' | 'due_soon' | 'overdue' | 'resolved'

export interface ServiceCustomerOption {
  id: number
  name: string
  email: string | null
  status?: string | null
  source?: string | null
}

export interface ServiceAgentOption {
  id: number
  fullName: string
  email: string
  role: string
}

export interface SlaDefinitionRecord {
  id: number
  name: string
  description: string | null
  category: ServiceTicketCategory | null
  priority: ServiceTicketPriority
  firstResponseMinutes: number
  resolutionMinutes: number
  warningBeforeMinutes: number
  autoEscalate: boolean
  escalationPriority: ServiceTicketPriority | null
  isActive: boolean
}

export interface ServiceTicketListMeta {
  counts: {
    open: number
    inProgress: number
    resolved: number
    overdue: number
  }
}

export interface ServiceTicketListResponse {
  data: ServiceTicketRecord[]
  meta: ServiceTicketListMeta
}

export interface ServiceTicketMutationResponse {
  message: string
  data: ServiceTicketRecord
}

export interface ServiceTicketOptionsResponse {
  customers: ServiceCustomerOption[]
  agents: ServiceAgentOption[]
  slaDefinitions: SlaDefinitionRecord[]
}

export interface ServiceTicketActivity {
  id: number
  activityType: string
  title: string
  description: string | null
  createdAt: string | null
  user: {
    id: number
    fullName: string
    email: string
  } | null
}

export interface ServiceTicketRecord {
  id: number
  code: string
  title?: string
  subject: string
  description: string
  category: ServiceTicketCategory
  status: ServiceTicketStatus
  priority: ServiceTicketPriority
  assigned_to?: number | null
  sla_id?: number | null
  escalationLevel: number
  alertState: ServiceTicketAlertState
  alerts: string[]
  firstResponseDueAt: string | null
  resolutionDueAt: string | null
  firstRespondedAt: string | null
  resolvedAt: string | null
  lastActivityAt: string | null
  customer: ServiceCustomerOption | null
  assignedUser: ServiceAgentOption | null
  slaDefinition: SlaDefinitionRecord | null
  activities: ServiceTicketActivity[]
}

export interface SlaAlertRecord {
  ticketId: number
  ticketCode: string
  subject: string
  alertState: ServiceTicketAlertState
  priority: ServiceTicketPriority
  resolutionDueAt: string | null
  customer: ServiceCustomerOption | null
  assignedUser: ServiceAgentOption | null
  alertCodes: string[]
}

export interface SlaDashboardResponse {
  summary: {
    activeDefinitions: number
    dueSoonTickets: number
    overdueTickets: number
    resolvedTickets: number
  }
  definitions: SlaDefinitionRecord[]
  alerts: SlaAlertRecord[]
  placeholder: {
    alert?: string
    escalation?: string
  }
}

export interface SlaMutationResponse {
  message: string
  data: SlaDefinitionRecord
}