import { z } from "zod";

export const IncidentTypeSchema = z.enum([
  "damage",
  "shortage",
  "overage",
  "expiration",
  "misplacement",
  "broken_packaging",
  "nonconforming_product",
  "picking_blocker",
  "lot_error",
]);

export const IncidentStatusSchema = z.enum([
  "open",
  "in_review",
  "resolved",
  "closed",
]);

export const IncidentSeveritySchema = z.enum(["low", "medium", "high"]);

export const CreateIncidentSchema = z.object({
  productId: z.string().min(1),
  locationId: z.string().min(1),
  type: IncidentTypeSchema,
  severity: IncidentSeveritySchema,
  description: z.string().min(1).max(2000),
  quantityAffected: z.number().int().positive(),
});

export const UpdateIncidentSchema = z
  .object({
    notes: z.string().min(1).max(5000),
    assignedTo: z.string().min(1),
  })
  .partial()
  .refine((data) => Object.keys(data).length > 0, {
    message: "At least one field must be provided",
  });

export type CreateIncidentInput = z.infer<typeof CreateIncidentSchema>;
export type UpdateIncidentInput = z.infer<typeof UpdateIncidentSchema>;
