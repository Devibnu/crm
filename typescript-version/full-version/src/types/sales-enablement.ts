export type SalesLeadStatus = 'new' | 'qualified' | 'disqualified'
export type OpportunityStage = 'new' | 'qualified' | 'proposal' | 'negotiation' | 'closed_won' | 'closed_lost' | 'prospecting'
export type QuotationStatus = 'draft' | 'submitted' | 'approved' | 'rejected'

export interface SalesUserOption {
  id: number
  fullName: string
  email: string
  role: string
}

export interface SalesLeadRecord {
  id: number
  code: string
  fullName: string
  full_name?: string
  email: string | null
  phone: string | null
  company: string | null
  source: string
  status: SalesLeadStatus
  assigned_user_id?: number | null
  qualificationNotes: string | null
  lastContactedAt: string | null
  qualifiedAt: string | null
  disqualifiedAt: string | null
  opportunitiesCount: number
  assignedUser: SalesUserOption | null
  capturedBy: {
    id: number
    fullName: string
  } | null
  metadata?: Record<string, unknown> | null
}

export interface LeadIndexResponse {
  summary: {
    total: number
    new: number
    qualified: number
    disqualified: number
  }
  salesUsers: SalesUserOption[]
  data: SalesLeadRecord[]
}

export interface LeadMutationResponse {
  message: string
  data: SalesLeadRecord
}

export interface OpportunityLeadOption {
  id: number
  code: string
  fullName: string
  company: string | null
  status: SalesLeadStatus
}

export interface OpportunityRecord {
  id: number
  code: string
  leadId: number
  name: string
  stage: OpportunityStage
  amount: number
  currency: string
  probability: number
  expectedCloseDate: string | null
  statusNotes: string | null
  closedAt: string | null
  quotationsCount: number
  lead: OpportunityLeadOption | null
  assignedUser: SalesUserOption | null
  metadata?: Record<string, unknown> | null
}

export interface OpportunityIndexResponse {
  summary: {
    pipelineValue: number
    weightedForecast: number
    closedWonValue: number
    totalOpenDeals: number
  }
  salesUsers: SalesUserOption[]
  qualifiedLeads: OpportunityLeadOption[]
  board: Array<{
    stage: OpportunityStage
    items: OpportunityRecord[]
  }>
  data: OpportunityRecord[]
}

export interface OpportunityMutationResponse {
  message: string
  data: OpportunityRecord
}

export interface QuotationOpportunityOption {
  id: number
  code: string
  name: string
  stage: OpportunityStage
  amount: number
  currency: string
  lead: {
    id: number
    fullName: string
    company: string | null
  } | null
}

export interface QuotationRecord {
  id: number
  quoteNumber: string
  opportunityId: number
  title: string
  amount: number
  currency: string
  validUntil: string | null
  status: QuotationStatus
  approvalNotes: string | null
  submittedAt: string | null
  approvedAt: string | null
  rejectedAt: string | null
  opportunity: QuotationOpportunityOption | null
  metadata?: Record<string, unknown> | null
}

export interface QuotationIndexResponse {
  summary: {
    draft: number
    submitted: number
    approved: number
    rejected: number
  }
  opportunities: QuotationOpportunityOption[]
  data: QuotationRecord[]
  placeholder: {
    approval?: string
  }
}

export interface QuotationMutationResponse {
  message: string
  data: QuotationRecord
}

export interface ForecastResponse {
  summary: {
    pipelineValue: number
    weightedForecast: number
    committedForecast: number
    closedWonValue: number
  }
  byStage: Array<{
    stage: OpportunityStage
    count: number
    amount: number
    weightedAmount: number
  }>
  openDeals: Array<{
    id: number
    code: string
    name: string
    stage: OpportunityStage
    amount: number
    probability: number
    expectedCloseDate: string | null
    lead: {
      fullName: string
      company: string | null
    } | null
  }>
  snapshots: Array<{
    id: number
    periodLabel: string
    snapshotDate: string | null
    forecastAmount: number
    weightedAmount: number
    committedAmount: number
    status: string
    notes: string | null
  }>
  placeholder: {
    forecastEngine?: string
  }
}