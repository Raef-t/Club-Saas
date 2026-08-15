"use client";

import { useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { Field } from "@/components/forms/FormControls";
import {
  createInitialReserveLockerForm,
  reserveLockerSchema,
} from "@/lib/validations/lockersSchema";
import { toIsoDate } from "@/components/forms/datePickerUtils";
import {
  LOCKER_HOLDER_TYPE_OPTIONS,
  LOCKER_RESERVATION_HOLDER_TYPES,
  LOCKER_RESERVATION_TYPE_OPTIONS,
} from "./lockerConstants";
import LockerHolderField from "./LockerHolderField";
import {
  createLockerCoachOptions,
  createLockerMemberOptions,
  createLockerReservationPayload,
  createLockerStaffOptions,
  getLockerValidationErrors,
} from "./lockerUtils";

const RESERVATION_HOLDER_OPTIONS = LOCKER_HOLDER_TYPE_OPTIONS.filter((option) =>
  LOCKER_RESERVATION_HOLDER_TYPES.includes(option.value),
);

/**
 * Collects and validates a new locker reservation.
 */
export default function LockerReserveForm({
  formId,
  members,
  coaches,
  staff,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
}) {
  const [form, setForm] = useState(() => createInitialReserveLockerForm());
  const [errors, setErrors] = useState({});
  const memberOptions = useMemo(() => createLockerMemberOptions(members), [members]);
  const coachOptions = useMemo(() => createLockerCoachOptions(coaches), [coaches]);
  const staffOptions = useMemo(() => createLockerStaffOptions(staff), [staff]);

  /**
   * Updates one reservation field and clears dependent holder state.
   */
  function updateField(field, value) {
    setForm((current) => {
      if (field === "holder_type") {
        if (value === "coach") {
          return {
            ...current,
            holder_type: value,
            holder_id: "",
            start_date: "",
            end_date: "",
          };
        }
        if (value === "member" && current.reservation_type === "assign") {
          const today = toIsoDate(new Date());
          return {
            ...current,
            holder_type: value,
            holder_id: "",
            start_date: today,
            end_date: today,
          };
        }
        return {
          ...current,
          holder_type: value,
          holder_id: "",
          start_date: current.start_date || toIsoDate(new Date()),
        };
      }

      if (field === "reservation_type") {
        if (value === "assign" && current.holder_type === "member") {
          const today = toIsoDate(new Date());
          return {
            ...current,
            reservation_type: value,
            price: "",
            start_date: today,
            end_date: today,
          };
        }
        return {
          ...current,
          reservation_type: value,
          price: value === "assign" ? "" : current.price,
        };
      }

      return { ...current, [field]: value };
    });

    if (errors[field]) {
      setErrors((current) => ({ ...current, [field]: null }));
    }
    if (field === "holder_type" && value === "coach") {
      setErrors((current) => ({ ...current, start_date: null, end_date: null }));
    }
  }

  /**
   * Validates and submits a normalized reservation payload.
   */
  function handleSubmit(event) {
    event.preventDefault();
    const validation = reserveLockerSchema.safeParse(createLockerReservationPayload(form));

    if (!validation.success) {
      setErrors(getLockerValidationErrors(validation.error));
      return;
    }

    setErrors({});
    onSubmit(validation.data);
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className="flex flex-col gap-5">
      {errorMessage && (
        <div
          className="rounded-xl border border-app-red/30 bg-app-red/10 p-4 text-sm text-app-red"
          role="alert"
        >
          {errorMessage}
        </div>
      )}

      <div className="flex flex-col gap-4">
        <div className="flex flex-col gap-1.5 text-start">
          <label className="flex items-center gap-1 text-sm font-medium text-white">
            نوع الحجز <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={LOCKER_RESERVATION_TYPE_OPTIONS}
            value={form.reservation_type}
            onChange={(value) => updateField("reservation_type", value)}
            error={errors.reservation_type}
          />
        </div>

        <div className="flex flex-col gap-1.5 text-start">
          <label className="flex items-center gap-1 text-sm font-medium text-white">
            نوع المستفيد <span className="text-app-red">*</span>
          </label>
          <Dropdown
            options={RESERVATION_HOLDER_OPTIONS}
            value={form.holder_type}
            onChange={(value) => updateField("holder_type", value)}
            error={errors.holder_type}
          />
        </div>

        <LockerHolderField
          holderType={form.holder_type}
          holderId={form.holder_id}
          memberOptions={memberOptions}
          coachOptions={coachOptions}
          staffOptions={staffOptions}
          onChange={(value) => updateField("holder_id", value)}
          error={errors.holder_id}
          required
        />

        {form.reservation_type === "rental" && (
          <Field
            label="السعر"
            type="number"
            required={false}
            value={form.price}
            onChange={(event) => updateField("price", event.target.value)}
            error={errors.price}
            min="0"
          />
        )}

        {form.holder_type !== "coach" && (
          <>
            <Field
              label="تاريخ البداية"
              type="date"
              required
              value={form.start_date}
              onChange={(value) => updateField("start_date", value)}
              error={errors.start_date}
            />

            <Field
              label="تاريخ النهاية (اختياري)"
              type="date"
              required={false}
              value={form.end_date}
              onChange={(value) => updateField("end_date", value)}
              error={errors.end_date}
            />
          </>
        )}
      </div>

      <div className="mt-4 flex items-center justify-end gap-3 border-t border-app-line pt-4">
        <Button type="button" tone="ghost" onClick={onCancel} disabled={isLoading}>
          إلغاء
        </Button>
        <Button type="submit" loading={isLoading}>
          تأكيد الحجز
        </Button>
      </div>
    </form>
  );
}
