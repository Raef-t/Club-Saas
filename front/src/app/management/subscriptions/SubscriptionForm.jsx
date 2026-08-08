"use client";

import { useState } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { formatLocalizedName } from "@/lib/utils";
import { subscriptionSchema } from "@/lib/validations/subscriptionsSchema";
import { getLocalDateValue, isDailyEntrySubscriptionPlan } from "./subscriptionUtils";

const CURRENCY_SYMBOL = "$";

/**
 * Collects and validates the values required to create a member subscription.
 */
export function SubscriptionCreateForm({
  members = [],
  plans = [],
  activities = [],
  coaches = [],
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
  initialMemberId = "",
  lockMemberId = false,
  submitLabel,
  cancelLabel,
  showAddAnother = true,
}) {
  const [form, setForm] = useState(() => {
    const initialPlan = plans[0] || null;
    const initialDate = isDailyEntrySubscriptionPlan(initialPlan) ? getLocalDateValue() : "";

    return {
      member_id: initialMemberId
        ? String(initialMemberId)
        : members[0]?.id
          ? String(members[0].id)
          : "",
      plan_id: initialPlan?.id ? String(initialPlan.id) : "",
      paid_amount: initialPlan?.base_price ? String(initialPlan.base_price) : "0",
      start_date: initialDate,
      end_date: initialDate,
    };
  });
  const [errors, setErrors] = useState({});
  const [submitAction, setSubmitAction] = useState("normal");
  const selectedPlanObj = plans.find((p) => String(p.id) === String(form.plan_id));
  const isDailyEntryPlan = isDailyEntrySubscriptionPlan(selectedPlanObj);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors && errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handlePlanChange(planId) {
    const nextPlan = plans.find((plan) => String(plan.id) === String(planId));
    const currentPlan = plans.find((plan) => String(plan.id) === String(form.plan_id));
    const nextIsDailyEntry = isDailyEntrySubscriptionPlan(nextPlan);
    const currentIsDailyEntry = isDailyEntrySubscriptionPlan(currentPlan);
    const today = nextIsDailyEntry ? getLocalDateValue() : "";

    setForm((current) => ({
      ...current,
      plan_id: planId,
      paid_amount: nextPlan ? String(nextPlan.base_price || "0") : current.paid_amount,
      start_date: nextIsDailyEntry ? today : currentIsDailyEntry ? "" : current.start_date,
      end_date: nextIsDailyEntry ? today : currentIsDailyEntry ? "" : current.end_date,
    }));

    setErrors((current) => ({
      ...current,
      plan_id: null,
      paid_amount: null,
      start_date: null,
      end_date: null,
    }));
  }

  function handleSubmit(event) {
    event.preventDefault();

    const dailyEntryDate = isDailyEntryPlan ? getLocalDateValue() : "";

    const validationData = {
      member_id: Number(form.member_id),
      plan_id: Number(form.plan_id),
      paid_amount: Number(form.paid_amount) || 0,
      start_date: dailyEntryDate || form.start_date || "",
      end_date: dailyEntryDate || form.end_date || "",
    };

    const result = subscriptionSchema.safeParse(validationData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(
      {
        ...validationData,
        payment_method: "cash",
        activities: [],
      },
      submitAction,
    );
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      {lockMemberId ? (
        <div className="block text-right text-sm text-app-muted-light">
          <span>اللاعب العضو</span>
          <div className="mt-2 flex h-11 items-center rounded-xl bg-app-card-soft px-3 text-white opacity-75">
            {(() => {
              const m = members.find((m) => String(m.id) === String(form.member_id));
              return m
                ? `${m.person?.full_name || `${m.first_name || ""} ${m.last_name || ""}`} (رقم العضوية: #${m.id})`
                : `العضو #${form.member_id}`;
            })()}
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          اللاعب العضو
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="bg-app-card-soft h-11"
            value={form.member_id}
            onChange={(val) => updateField("member_id", val)}
            options={members.map((m) => ({
              value: String(m.id),
              label: `${m.person?.full_name || `${m.first_name || ""} ${m.last_name || ""}`} (رقم العضوية: #${m.id})`,
            }))}
            placeholder="اختر اللاعب"
            error={errors && errors.member_id}
          />
        </label>
      )}

      <label className="block text-right text-sm text-app-muted-light">
        خطة الاشتراك
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={form.plan_id}
          onChange={handlePlanChange}
          options={plans.map((p) => ({
            value: String(p.id),
            label: formatLocalizedName(p.name) || p.name || "",
          }))}
          placeholder="اختر الخطة"
          error={errors && errors.plan_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        المبلغ المدفوع للاشتراك ({CURRENCY_SYMBOL})
        <input
          type="number"
          min="0"
          value={form.paid_amount}
          onChange={(e) => updateField("paid_amount", e.target.value)}
          aria-invalid={Boolean(errors && errors.paid_amount)}
          className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
            errors && errors.paid_amount
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          placeholder={selectedPlanObj ? `السعر الأساسي: ${selectedPlanObj.base_price}` : ""}
          required
        />
        {errors && errors.paid_amount && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.paid_amount}
          </span>
        )}
      </label>

      <div className="grid grid-cols-2 gap-3">
        <div>
          <DatePickerSmart
            label="تاريخ بداية الاشتراك *"
            value={form.start_date}
            onChange={(val) => updateField("start_date", val)}
            compact={false}
            disabled={isDailyEntryPlan}
            allowClear={!isDailyEntryPlan}
            error={errors && errors.start_date}
          />
          {errors && errors.start_date && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.start_date}
            </span>
          )}
        </div>

        <div>
          <DatePickerSmart
            label="تاريخ نهاية الاشتراك *"
            value={form.end_date}
            onChange={(val) => updateField("end_date", val)}
            compact={false}
            disabled={isDailyEntryPlan}
            allowClear={!isDailyEntryPlan}
            error={errors && errors.end_date}
          />
          {errors && errors.end_date && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.end_date}
            </span>
          )}
        </div>
      </div>

      {isDailyEntryPlan && (
        <p className="text-right text-xs text-app-muted-light">
          خطة دخولية ليوم واحد؛ تم ضبط تاريخ البداية والنهاية تلقائياً على تاريخ اليوم.
        </p>
      )}

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}>
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          {cancelLabel || "إلغاء"}
        </Button>
        <Button
          type="submit"
          className="h-11 flex-1"
          loading={isLoading && submitAction === "normal"}
          onClick={() => setSubmitAction("normal")}
        >
          {submitLabel || "إنشاء الاشتراك"}
        </Button>
        {showAddAnother && (
          <Button
            type="submit"
            tone="outline"
            className="h-11 flex-1 border-app-yellow text-app-yellow hover:bg-app-yellow/10"
            loading={isLoading && submitAction === "addAnother"}
            onClick={() => setSubmitAction("addAnother")}
          >
            حفظ وإضافة آخر
          </Button>
        )}
      </div>
    </form>
  );
}
