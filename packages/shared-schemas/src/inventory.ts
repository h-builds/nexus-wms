import { z } from "zod";

export const InventoryStatusSchema = z.enum([
  "available",
  "blocked",
  "in_transit",
  "quarantine",
]);

export const StockItemSchema = z.object({
  id: z.string(),
  productId: z.string(),
  locationId: z.string(),
  quantityOnHand: z.number().int().nonnegative(),
  quantityAvailable: z.number().int().nonnegative(),
  quantityBlocked: z.number().int().nonnegative(),
  lotNumber: z.string().optional(),
  serialNumber: z.string().optional(),
  receivedAt: z.string().datetime().optional(),
  expiresAt: z.string().datetime().optional(),
  status: InventoryStatusSchema,
  version: z.number().int().positive(),
  updatedAt: z.string().datetime(),
});

export type StockItemInput = z.infer<typeof StockItemSchema>;
