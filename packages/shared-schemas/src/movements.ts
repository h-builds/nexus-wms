import { z } from "zod";

export const MovementTypeSchema = z.enum([
  "receipt",
  "putaway",
  "relocation",
  "adjustment",
  "picking",
  "return_internal",
]);

export const AdjustmentReasonSchema = z.enum([
  "manual_adjustment",
  "cycle_count",
  "incident_damage",
  "incident_shortage",
  "quality_hold",
  "correction",
]);

export const CreateMovementSchema = z
  .object({
    productId: z.string().min(1),
    fromLocationId: z.string().min(1).optional(),
    toLocationId: z.string().min(1).optional(),
    type: MovementTypeSchema,
    quantity: z.number().int().positive(),
    reason: AdjustmentReasonSchema.optional(),
    reference: z.string().optional(),
  })
  .superRefine((data, ctx) => {
    // Receipt: toLocationId required, fromLocationId not required
    if (data.type === "receipt" && !data.toLocationId) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "toLocationId is required for receipt movements",
        path: ["toLocationId"],
      });
    }
    // Picking: fromLocationId required
    if (data.type === "picking" && !data.fromLocationId) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "fromLocationId is required for picking movements",
        path: ["fromLocationId"],
      });
    }
    // Relocation, putaway, return_internal: both required and must differ
    if (["relocation", "putaway", "return_internal"].includes(data.type)) {
      if (!data.fromLocationId) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: `fromLocationId is required for ${data.type} movements`,
          path: ["fromLocationId"],
        });
      }
      if (!data.toLocationId) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: `toLocationId is required for ${data.type} movements`,
          path: ["toLocationId"],
        });
      }
      if (
        data.fromLocationId &&
        data.toLocationId &&
        data.fromLocationId === data.toLocationId
      ) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: "fromLocationId and toLocationId must be different",
          path: ["toLocationId"],
        });
      }
    }
    // Adjustment: fromLocationId required
    if (data.type === "adjustment" && !data.fromLocationId) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "fromLocationId is required for adjustment movements",
        path: ["fromLocationId"],
      });
    }
  });

export type CreateMovementInput = z.infer<typeof CreateMovementSchema>;
