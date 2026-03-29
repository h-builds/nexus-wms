export interface Product {
  id: string
  sku: string
  name: string
  category: string
  unitOfMeasure: string
}

export interface StockItem {
  id: string
  productId: string
  locationId: string
  quantityOnHand: number
  quantityAvailable: number
  quantityBlocked: number
  lotNumber: string | null
  serialNumber: string | null
  receivedAt: string
  expiresAt: string | null
  status: string
}

export interface IncidentPayload {
  productId: string
  locationId: string
  type: string
  severity: string
  description: string
  quantityAffected: number
}

export interface MovementPayload {
  type: string
  productId: string
  fromLocationId?: string
  toLocationId?: string
  quantity: number
  reference?: string
  reason?: string
}
