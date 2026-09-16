"use client";

import { useState } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { CURRENCY_SYMBOL, formatLocalizedName, formatMoney } from "@/lib/utils";
import { subscriptionEditSchema, subscriptionSchema } from "@/lib/validations/subscriptionsSchema";
import {
  getLocalDateValue,
  getSubscriptionSplitPaymentAmounts,
  getSubscriptionReceiptNumbers,
  getSubscriptionEndDate,
  isDailyEntrySubscriptionPlan,
  isPrivateSubscriptionPlan,
  calculateDiscountFromFinalPrice,
  calculateDiscountFromPercentage,
  getSubscriptionDiscountSummary,
  getSubscriptionOriginalAmounts,
} from "./subscriptionUtils";
import { SUBSCRIPTION_STATUS_OPTIONS } from "./subscriptionConstants";
import { getMemberAccountName } from "@/lib/memberIdentity";
import SubscriptionDiscountFields from "./SubscriptionDiscountFields";

function getResetDiscountFields(plan, monthsCount = 1) {
  const { originalTotal, coachOriginal, branchOriginal } = getSubscriptionOriginalAmounts(
    plan,
    monthsCount,
  );

  return {
    is_discount: false,
    discount_mode: "unified",
    discount_percentage: 0,
    discount_amount: 0,
    discount_reason: "",
    final_price: originalTotal,
    coach_final_price: coachOriginal,
    branch_final_price: branchOriginal,
    coach_discount_percentage: 0,
    branch_discount_percentage: 0,
    coach_paid_amount: coachOriginal,
    branch_paid_amount: branchOriginal,
    paid_amount: originalTotal,
  };
}

function getUnifiedDiscountFields(originalAmounts, percentageOrPrice, source = "percentage") {
  const calculation =
    source === "price"
      ? calculateDiscountFromFinalPrice(originalAmounts.originalTotal, percentageOrPrice)
      : calculateDiscountFromPercentage(originalAmounts.originalTotal, percentageOrPrice);
  const coach = calculateDiscountFromPercentage(
    originalAmounts.coachOriginal,
    calculation.discountPercentage,
  );
  const branch = calculateDiscountFromPercentage(
    originalAmounts.branchOriginal,
    calculation.discountPercentage,
  );

  return {
    discount_percentage: calculation.discountPercentage,
    discount_amount: calculation.discountAmount,
    final_price: calculation.finalPrice,
    coach_discount_percentage: calculation.discountPercentage,
    branch_discount_percentage: calculation.discountPercentage,
    coach_final_price: coach.finalPrice,
    branch_final_price: branch.finalPrice,
    coach_paid_amount: coach.finalPrice,
    branch_paid_amount: branch.finalPrice,
    paid_amount: calculation.finalPrice,
  };
}

function getSplitDiscountFields(current, originalAmounts, side, value, source = "price") {
  const original =
    side === "coach" ? originalAmounts.coachOriginal : originalAmounts.branchOriginal;
  const calculation =
    source === "price"
      ? calculateDiscountFromFinalPrice(original, value)
      : calculateDiscountFromPercentage(original, value);
  const otherFinal =
    Number(side === "coach" ? current.branch_final_price : current.coach_final_price) || 0;
  const finalPrice = Number((calculation.finalPrice + otherFinal).toFixed(2));
  const overall = calculateDiscountFromFinalPrice(originalAmounts.originalTotal, finalPrice);

  return {
    [`${side}_final_price`]: calculation.finalPrice,
    [`${side}_discount_percentage`]: calculation.discountPercentage,
    [`${side}_paid_amount`]: calculation.finalPrice,
    final_price: finalPrice,
    discount_percentage: overall.discountPercentage,
    discount_amount: overall.discountAmount,
    paid_amount: finalPrice,
  };
}

function getSplitDiscountFieldsFromPercentages(current, originalAmounts) {
  const coach = calculateDiscountFromPercentage(
    originalAmounts.coachOriginal,
    current.coach_discount_percentage,
  );
  const branch = calculateDiscountFromPercentage(
    originalAmounts.branchOriginal,
    current.branch_discount_percentage,
  );
  const finalPrice = Number((coach.finalPrice + branch.finalPrice).toFixed(2));
  const overall = calculateDiscountFromFinalPrice(originalAmounts.originalTotal, finalPrice);

  return {
    coach_final_price: coach.finalPrice,
    branch_final_price: branch.finalPrice,
    coach_paid_amount: coach.finalPrice,
    branch_paid_amount: branch.finalPrice,
    final_price: finalPrice,
    paid_amount: finalPrice,
    discount_percentage: overall.discountPercentage,
    discount_amount: overall.discountAmount,
  };
}

/**
 * Collects and validates the values required to create a member subscription.
 */
export function SubscriptionCreateForm({
  members = [],
  plans = [],
  activityTypes = [],
  selectedActivityTypeId = "",
  onActivityTypeChange,
  isPlansLoading = false,
  plansErrorMessage = "",
  isActivityTypesLoading = false,
  activityTypesErrorMessage = "",
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
      months_count: "1",
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: initialDate,
      end_date: initialDate,
      ...getResetDiscountFields(initialPlan, 1),
    };
  });
  const [errors, setErrors] = useState({});
  const [submitAction, setSubmitAction] = useState("normal");
  const selectedPlanObj = plans.find((p) => String(p.id) === String(form.plan_id));
  const selectedActivityType = activityTypes.find(
    (activityType) => String(activityType.id) === String(selectedActivityTypeId),
  );
  const isDailyEntryPlan = isDailyEntrySubscriptionPlan(selectedPlanObj);
  const isPrivatePlan = isPrivateSubscriptionPlan(selectedPlanObj, selectedActivityType);
  const originalAmounts = getSubscriptionOriginalAmounts(selectedPlanObj, form.months_count);

  function updateField(field, value) {
    setForm((current) => {
      const nextState = { ...current, [field]: value };
      if (!isDailyEntryPlan && (field === "start_date" || field === "months_count")) {
        const startDate = field === "start_date" ? value : current.start_date;
        const months = field === "months_count" ? value : current.months_count;
        if (startDate) nextState.end_date = getSubscriptionEndDate(startDate, months);
      }
      return nextState;
    });
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
      ...getResetDiscountFields(nextPlan, nextIsDailyEntry ? 1 : current.months_count),
      months_count: nextIsDailyEntry ? "1" : current.months_count,
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: nextIsDailyEntry ? today : currentIsDailyEntry ? "" : current.start_date,
      end_date: nextIsDailyEntry ? today : currentIsDailyEntry ? "" : current.end_date,
    }));

    setErrors((current) => ({
      ...current,
      plan_id: null,
      paid_amount: null,
      months_count: null,
      receipt_number: null,
      coach_receipt_number: null,
      branch_receipt_number: null,
      start_date: null,
      end_date: null,
    }));
  }

  function handleActivityTypeChange(activityTypeId) {
    setForm((current) => ({
      ...current,
      plan_id: "",
      ...getResetDiscountFields(null, current.months_count),
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: isDailyEntryPlan ? "" : current.start_date,
      end_date: isDailyEntryPlan ? "" : current.end_date,
    }));
    setErrors((current) => ({ ...current, plan_id: null, paid_amount: null }));
    onActivityTypeChange?.(activityTypeId);
  }

  function handleMonthsChange(value) {
    const nextMonths = isDailyEntryPlan ? "1" : value;
    const nextOriginalAmounts = getSubscriptionOriginalAmounts(selectedPlanObj, nextMonths);

    setForm((current) => ({
      ...current,
      months_count: nextMonths,
      ...(current.is_discount
        ? current.discount_mode === "split" && isPrivatePlan
          ? getSplitDiscountFieldsFromPercentages(current, nextOriginalAmounts)
          : getUnifiedDiscountFields(nextOriginalAmounts, current.discount_percentage, "percentage")
        : getResetDiscountFields(selectedPlanObj, nextMonths)),
      end_date:
        current.start_date && !isDailyEntryPlan
          ? getSubscriptionEndDate(current.start_date, nextMonths)
          : current.end_date,
    }));
    setErrors((current) => ({ ...current, months_count: null, paid_amount: null }));
  }

  function toggleDiscount(checked) {
    setForm((current) => ({
      ...current,
      ...getResetDiscountFields(selectedPlanObj, current.months_count),
      is_discount: checked,
    }));
  }

  function changeDiscountMode(mode) {
    setForm((current) => ({
      ...current,
      discount_mode: mode,
      ...getUnifiedDiscountFields(originalAmounts, current.discount_percentage, "percentage"),
    }));
  }

  function changeUnifiedDiscount(value, source) {
    setForm((current) => ({
      ...current,
      ...getUnifiedDiscountFields(originalAmounts, value, source),
    }));
  }

  function changeSplitDiscount(side, value, source) {
    setForm((current) => ({
      ...current,
      ...getSplitDiscountFields(current, originalAmounts, side, value, source),
    }));
  }

  function changePrivatePaidAmount(side, value) {
    setForm((current) => {
      const normalized = Math.max(0, Number(value) || 0);
      const other =
        Number(side === "coach" ? current.branch_paid_amount : current.coach_paid_amount) || 0;
      return {
        ...current,
        [`${side}_paid_amount`]: value,
        paid_amount: Number((normalized + other).toFixed(2)),
      };
    });
  }

  function handleSubmit(event) {
    event.preventDefault();

    const dailyEntryDate = isDailyEntryPlan ? getLocalDateValue() : "";

    const validationData = {
      member_id: Number(form.member_id),
      plan_id: Number(form.plan_id),
      paid_amount: Number(form.paid_amount) || 0,
      months_count: isDailyEntryPlan ? 1 : form.months_count,
      receipt_number: form.receipt_number,
      coach_receipt_number: form.coach_receipt_number,
      branch_receipt_number: form.branch_receipt_number,
      is_private_plan: isPrivatePlan,
      is_discount: form.is_discount,
      discount_percentage: form.is_discount ? Number(form.discount_percentage) || 0 : 0,
      discount_amount: form.is_discount ? Number(form.discount_amount) || 0 : 0,
      discount_reason: form.is_discount ? form.discount_reason : "",
      coach_discount_percentage:
        isPrivatePlan && form.is_discount ? Number(form.coach_discount_percentage) || 0 : undefined,
      branch_discount_percentage:
        isPrivatePlan && form.is_discount
          ? Number(form.branch_discount_percentage) || 0
          : undefined,
      coach_paid_amount: isPrivatePlan ? Number(form.coach_paid_amount) || 0 : undefined,
      branch_paid_amount: isPrivatePlan ? Number(form.branch_paid_amount) || 0 : undefined,
      currency: "SYP",
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
        ...result.data,
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
              if (!m) return "العضو المحدد";

              const memberName =
                m.person?.full_name ||
                `${m.first_name || ""} ${m.last_name || ""}`.trim() ||
                "عضو بدون اسم";
              const accountName = getMemberAccountName(m);

              return accountName ? `${memberName} (${accountName})` : memberName;
            })()}
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          اللاعب العضو
          <Dropdown
            searchable
            searchPlaceholder="ابحث عن اللاعب بالاسم..."
            className="mt-2 text-white"
            buttonClassName="bg-app-card-soft h-11"
            value={form.member_id}
            onChange={(val) => updateField("member_id", val)}
            options={members.map((m) => ({
              value: String(m.id),
              label:
                m.person?.full_name ||
                `${m.first_name || ""} ${m.last_name || ""}`.trim() ||
                "عضو بدون اسم",
            }))}
            placeholder="اختر اللاعب"
            error={errors && errors.member_id}
          />
        </label>
      )}

      <label className="block text-right text-sm text-app-muted-light">
        نوع النشاط
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={selectedActivityTypeId}
          onChange={handleActivityTypeChange}
          options={[
            { value: "", label: "الكل" },
            ...activityTypes.map((activityType) => ({
              value: String(activityType.id),
              label: formatLocalizedName(activityType.name) || `نوع النشاط #${activityType.id}`,
            })),
          ]}
          placeholder={isActivityTypesLoading ? "جاري تحميل أنواع الأنشطة..." : "اختر نوع النشاط"}
          disabled={isActivityTypesLoading}
        />
        {activityTypesErrorMessage && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {activityTypesErrorMessage}
          </span>
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        خطة الاشتراك
        {isPlansLoading ? (
          <div
            className="mt-2 flex h-11 items-center justify-center gap-2 rounded-xl border border-app-line bg-app-card-soft text-xs text-app-muted-light"
            role="status"
          >
            <span className="size-4 animate-spin rounded-full border-2 border-app-muted border-t-app-yellow" />
            جاري تحميل باقات الاشتراك...
          </div>
        ) : (
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
            disabled={plans.length === 0 || Boolean(plansErrorMessage)}
            error={errors && errors.plan_id}
          />
        )}
        {plansErrorMessage ? (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {plansErrorMessage}
          </span>
        ) : (
          !isPlansLoading &&
          plans.length === 0 && (
            <span className="mt-1.5 block text-xs text-app-muted-light" role="status">
              {selectedActivityTypeId
                ? "لا توجد باقات اشتراك متاحة لنوع النشاط المحدد"
                : "لا توجد باقات اشتراك متاحة حالياً"}
            </span>
          )
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        عدد الأشهر *
        <input
          type="number"
          min="1"
          step="1"
          value={form.months_count}
          onChange={(event) => handleMonthsChange(event.target.value)}
          disabled={isDailyEntryPlan}
          aria-invalid={Boolean(errors && errors.months_count)}
          className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none disabled:opacity-60 ${
            errors && errors.months_count
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          required
        />
        {errors && errors.months_count && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.months_count}
          </span>
        )}
      </label>

      <SubscriptionDiscountFields
        form={form}
        originalTotal={originalAmounts.originalTotal}
        coachOriginal={originalAmounts.coachOriginal}
        branchOriginal={originalAmounts.branchOriginal}
        isPrivatePlan={isPrivatePlan}
        errors={errors}
        onToggle={toggleDiscount}
        onModeChange={changeDiscountMode}
        onFinalPriceChange={(value) => changeUnifiedDiscount(value, "price")}
        onPercentageChange={(value) => changeUnifiedDiscount(value, "percentage")}
        onCoachPriceChange={(value) => changeSplitDiscount("coach", value, "price")}
        onCoachPercentageChange={(value) => changeSplitDiscount("coach", value, "percentage")}
        onBranchPriceChange={(value) => changeSplitDiscount("branch", value, "price")}
        onBranchPercentageChange={(value) => changeSplitDiscount("branch", value, "percentage")}
        onReasonChange={(value) => updateField("discount_reason", value)}
      />

      {isPrivatePlan ? (
        <div className="grid gap-3 rounded-xl border border-app-line bg-app-card-soft/40 p-4 sm:grid-cols-2">
          <label className="block text-right text-sm text-app-muted-light">
            دفعة الكوتش ({CURRENCY_SYMBOL})
            <input
              type="number"
              min="0"
              max={form.coach_final_price}
              step="0.01"
              value={form.coach_paid_amount}
              onChange={(event) => changePrivatePaidAmount("coach", event.target.value)}
              className="app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none focus:border-app-yellow/70"
            />
          </label>
          <label className="block text-right text-sm text-app-muted-light">
            دفعة النادي ({CURRENCY_SYMBOL})
            <input
              type="number"
              min="0"
              max={form.branch_final_price}
              step="0.01"
              value={form.branch_paid_amount}
              onChange={(event) => changePrivatePaidAmount("branch", event.target.value)}
              className="app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none focus:border-app-yellow/70"
            />
          </label>
          <p className="text-xs text-app-muted-light sm:col-span-2">
            إجمالي المدفوع:{" "}
            <span className="font-medium text-app-green">{formatMoney(form.paid_amount)}</span>
          </p>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          المبلغ المدفوع للاشتراك ({CURRENCY_SYMBOL})
          <input
            type="number"
            min="0"
            max={form.final_price}
            step="0.01"
            value={form.paid_amount}
            onChange={(event) => updateField("paid_amount", event.target.value)}
            aria-invalid={Boolean(errors.paid_amount)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
              errors.paid_amount
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            required
          />
          {errors.paid_amount && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.paid_amount}
            </span>
          )}
        </label>
      )}

      {isPrivatePlan ? (
        <div className="rounded-xl border border-yellow-400/25 bg-yellow-400/[0.04] p-4">
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال الكوتش *
              <span className="ms-1 text-xs text-app-yellow">
                ({formatMoney(selectedPlanObj?.coach_price)})
              </span>
              <input
                type="text"
                value={form.coach_receipt_number}
                onChange={(event) => updateField("coach_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors && errors.coach_receipt_number)}
                aria-describedby={
                  errors && errors.coach_receipt_number ? "coach-receipt-number-error" : undefined
                }
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors && errors.coach_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال الكوتش"
                maxLength={100}
                required
              />
              {errors && errors.coach_receipt_number && (
                <span
                  id="coach-receipt-number-error"
                  className="mt-1.5 block text-xs text-app-red"
                  role="alert"
                >
                  {errors.coach_receipt_number}
                </span>
              )}
            </label>

            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال النادي *
              <span className="ms-1 text-xs text-app-yellow">
                ({formatMoney(selectedPlanObj?.branch_price)})
              </span>
              <input
                type="text"
                value={form.branch_receipt_number}
                onChange={(event) => updateField("branch_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors && errors.branch_receipt_number)}
                aria-describedby={
                  errors && errors.branch_receipt_number ? "branch-receipt-number-error" : undefined
                }
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors && errors.branch_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال النادي"
                maxLength={100}
                required
              />
              {errors && errors.branch_receipt_number && (
                <span
                  id="branch-receipt-number-error"
                  className="mt-1.5 block text-xs text-app-red"
                  role="alert"
                >
                  {errors.branch_receipt_number}
                </span>
              )}
            </label>
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          رقم الإيصال *
          <input
            type="text"
            value={form.receipt_number}
            onChange={(event) => updateField("receipt_number", event.target.value)}
            aria-invalid={Boolean(errors && errors.receipt_number)}
            aria-describedby={errors && errors.receipt_number ? "receipt-number-error" : undefined}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
              errors && errors.receipt_number
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            placeholder="أدخل رقم الإيصال"
            maxLength={100}
            required
          />
          {errors && errors.receipt_number && (
            <span
              id="receipt-number-error"
              className="mt-1.5 block text-xs text-app-red"
              role="alert"
            >
              {errors.receipt_number}
            </span>
          )}
        </label>
      )}

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

/**
 * Edits the fields supported by PUT /player-subscriptions/{id}.
 */
export function SubscriptionEditForm({
  subscription,
  members = [],
  plans = [],
  activityTypes = [],
  selectedActivityTypeId = "",
  onActivityTypeChange,
  isPlansLoading = false,
  plansErrorMessage = "",
  isActivityTypesLoading = false,
  activityTypesErrorMessage = "",
  onSubmit,
  onCancel,
  isLoading = false,
  errorMessage = "",
  canEditPaidAmount = false,
}) {
  const initialReceiptNumbers = getSubscriptionReceiptNumbers(subscription);
  const initialDiscountSummary = getSubscriptionDiscountSummary(subscription);
  const initialOriginalAmounts = getSubscriptionOriginalAmounts(
    subscription?.plan,
    subscription?.months_count,
  );
  const initialSplitPayments = getSubscriptionSplitPaymentAmounts(subscription);
  const [form, setForm] = useState(() => ({
    member_id: String(subscription?.member_id || subscription?.member?.id || ""),
    plan_id: String(subscription?.plan_id || subscription?.plan?.id || ""),
    offer_id: String(subscription?.offer_id || subscription?.offer?.id || ""),
    months_count: String(subscription?.months_count || 1),
    start_date: String(subscription?.start_date || "").split("T")[0],
    end_date: String(subscription?.end_date || "").split("T")[0],
    status: subscription?.status || "active",
    paid_amount: String(subscription?.paid_amount ?? 0),
    receipt_number: String(initialReceiptNumbers.receiptNumber ?? ""),
    coach_receipt_number: String(initialReceiptNumbers.coachReceiptNumber ?? ""),
    branch_receipt_number: String(initialReceiptNumbers.branchReceiptNumber ?? ""),
    is_discount: initialDiscountSummary.isDiscount,
    discount_mode:
      Number(subscription?.coach_discount_percentage || 0) !==
      Number(subscription?.branch_discount_percentage || 0)
        ? "split"
        : "unified",
    discount_percentage: initialDiscountSummary.discountPercentage,
    discount_amount: initialDiscountSummary.discountAmount,
    discount_reason: subscription?.discount_reason || "",
    final_price: initialDiscountSummary.finalPrice,
    coach_discount_percentage: Number(
      subscription?.coach_discount_percentage ?? initialDiscountSummary.discountPercentage,
    ),
    branch_discount_percentage: Number(
      subscription?.branch_discount_percentage ?? initialDiscountSummary.discountPercentage,
    ),
    coach_final_price: Math.max(
      0,
      initialOriginalAmounts.coachOriginal -
        (initialOriginalAmounts.coachOriginal *
          Number(
            subscription?.coach_discount_percentage ?? initialDiscountSummary.discountPercentage,
          )) /
          100,
    ),
    branch_final_price: Math.max(
      0,
      initialOriginalAmounts.branchOriginal -
        (initialOriginalAmounts.branchOriginal *
          Number(
            subscription?.branch_discount_percentage ?? initialDiscountSummary.discountPercentage,
          )) /
          100,
    ),
    coach_paid_amount: initialSplitPayments.coachPaidAmount,
    branch_paid_amount: initialSplitPayments.branchPaidAmount,
    notes: subscription?.notes || "",
    reason: "",
  }));
  const [errors, setErrors] = useState({});
  const selectedPlan = plans.find((plan) => String(plan.id) === String(form.plan_id));
  const resolvedPlan = selectedPlan || subscription?.plan;
  const selectedActivityType = activityTypes.find(
    (activityType) => String(activityType.id) === String(selectedActivityTypeId),
  );
  const isDailyEntryPlan = isDailyEntrySubscriptionPlan(resolvedPlan);
  const isPrivatePlan = isPrivateSubscriptionPlan(resolvedPlan, selectedActivityType);
  const originalAmounts = getSubscriptionOriginalAmounts(resolvedPlan, form.months_count);
  function updateField(field, value) {
    setForm((current) => {
      const nextState = { ...current, [field]: value };
      if (!isDailyEntryPlan && (field === "start_date" || field === "months_count")) {
        const startDate = field === "start_date" ? value : current.start_date;
        const months = field === "months_count" ? value : current.months_count;
        if (startDate) nextState.end_date = getSubscriptionEndDate(startDate, months);
      }
      return nextState;
    });
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handlePlanChange(planId) {
    const nextPlan = plans.find((plan) => String(plan.id) === String(planId));
    const nextIsDailyEntry = isDailyEntrySubscriptionPlan(nextPlan);
    const today = nextIsDailyEntry ? getLocalDateValue() : "";

    setForm((current) => {
      const startDate = nextIsDailyEntry ? today : current.start_date;

      return {
        ...current,
        plan_id: planId,
        ...getResetDiscountFields(nextPlan, current.months_count),
        receipt_number: "",
        coach_receipt_number: "",
        branch_receipt_number: "",
        start_date: startDate,
        end_date: nextIsDailyEntry
          ? today
          : getSubscriptionEndDate(startDate, current.months_count),
      };
    });
    setErrors((current) => ({
      ...current,
      plan_id: null,
      paid_amount: null,
      receipt_number: null,
      coach_receipt_number: null,
      branch_receipt_number: null,
      start_date: null,
      end_date: null,
    }));
  }

  function handleActivityTypeChange(activityTypeId) {
    setForm((current) => ({
      ...current,
      plan_id: "",
      ...getResetDiscountFields(null, current.months_count),
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: isDailyEntryPlan ? "" : current.start_date,
      end_date: isDailyEntryPlan ? "" : current.end_date,
    }));
    setErrors((current) => ({ ...current, plan_id: null }));
    onActivityTypeChange?.(activityTypeId);
  }

  function handleEditMonthsChange(value) {
    const nextMonths = isDailyEntryPlan ? "1" : value;
    const nextOriginalAmounts = getSubscriptionOriginalAmounts(resolvedPlan, nextMonths);
    setForm((current) => ({
      ...current,
      months_count: nextMonths,
      ...(current.is_discount
        ? current.discount_mode === "split" && isPrivatePlan
          ? getSplitDiscountFieldsFromPercentages(current, nextOriginalAmounts)
          : getUnifiedDiscountFields(nextOriginalAmounts, current.discount_percentage, "percentage")
        : getResetDiscountFields(resolvedPlan, nextMonths)),
      end_date:
        current.start_date && !isDailyEntryPlan
          ? getSubscriptionEndDate(current.start_date, nextMonths)
          : current.end_date,
    }));
    setErrors((current) => ({ ...current, months_count: null, paid_amount: null }));
  }

  function toggleEditDiscount(checked) {
    setForm((current) => ({
      ...current,
      ...getResetDiscountFields(resolvedPlan, current.months_count),
      is_discount: checked,
    }));
  }

  function changeEditDiscountMode(mode) {
    setForm((current) => ({
      ...current,
      discount_mode: mode,
      ...getUnifiedDiscountFields(originalAmounts, current.discount_percentage, "percentage"),
    }));
  }

  function changeEditUnifiedDiscount(value, source) {
    setForm((current) => ({
      ...current,
      ...getUnifiedDiscountFields(originalAmounts, value, source),
    }));
  }

  function changeEditSplitDiscount(side, value, source) {
    setForm((current) => ({
      ...current,
      ...getSplitDiscountFields(current, originalAmounts, side, value, source),
    }));
  }

  function changeEditPrivatePaidAmount(side, value) {
    setForm((current) => {
      const normalized = Math.max(0, Number(value) || 0);
      const other =
        Number(side === "coach" ? current.branch_paid_amount : current.coach_paid_amount) || 0;
      return {
        ...current,
        [`${side}_paid_amount`]: value,
        paid_amount: Number((normalized + other).toFixed(2)),
      };
    });
  }

  function handleSubmit(event) {
    event.preventDefault();
    const validationData = {
      member_id: form.member_id,
      plan_id: form.plan_id,
      offer_id: form.offer_id ? form.offer_id : null,
      months_count: form.months_count,
      start_date: form.start_date,
      end_date: form.end_date,
      status: form.status,
      paid_amount: form.paid_amount,
      payment_method: "cash",
      ...(isPrivatePlan
        ? {
            coach_receipt_number: form.coach_receipt_number,
            branch_receipt_number: form.branch_receipt_number,
            coach_paid_amount: Number(form.coach_paid_amount) || 0,
            branch_paid_amount: Number(form.branch_paid_amount) || 0,
          }
        : { receipt_number: form.receipt_number }),
      is_discount: form.is_discount,
      discount_percentage: form.is_discount ? Number(form.discount_percentage) || 0 : 0,
      discount_amount: form.is_discount ? Number(form.discount_amount) || 0 : 0,
      discount_reason: form.is_discount ? form.discount_reason : "",
      coach_discount_percentage:
        isPrivatePlan && form.is_discount ? Number(form.coach_discount_percentage) || 0 : undefined,
      branch_discount_percentage:
        isPrivatePlan && form.is_discount
          ? Number(form.branch_discount_percentage) || 0
          : undefined,
      currency: subscription?.currency || subscription?.currency_type || "SYP",
      notes: form.notes.trim(),
      reason: form.reason,
    };
    const result = subscriptionEditSchema.safeParse(validationData);

    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        const key = issue.path.join("_");
        if (!formattedErrors[key]) formattedErrors[key] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(result.data);
  }

  return (
    <form noValidate onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        اللاعب العضو *
        <Dropdown
          searchable
          searchPlaceholder="ابحث عن اللاعب بالاسم..."
          className="mt-2 text-white"
          buttonClassName="h-11 bg-app-card-soft"
          value={form.member_id}
          onChange={(value) => updateField("member_id", value)}
          options={members.map((member) => ({
            value: String(member.id),
            label:
              member.person?.full_name ||
              `${member.first_name || ""} ${member.last_name || ""}`.trim() ||
              "عضو بدون اسم",
          }))}
          error={errors.member_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        نوع النشاط
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="h-11 bg-app-card-soft"
          value={selectedActivityTypeId}
          onChange={handleActivityTypeChange}
          options={[
            { value: "", label: "الكل" },
            ...activityTypes.map((activityType) => ({
              value: String(activityType.id),
              label: formatLocalizedName(activityType.name) || `نوع النشاط #${activityType.id}`,
            })),
          ]}
          placeholder={isActivityTypesLoading ? "جاري تحميل أنواع الأنشطة..." : "اختر نوع النشاط"}
          disabled={isActivityTypesLoading}
        />
        {activityTypesErrorMessage && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {activityTypesErrorMessage}
          </span>
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        خطة الاشتراك *
        {isPlansLoading ? (
          <div
            className="mt-2 flex h-11 items-center justify-center gap-2 rounded-xl border border-app-line bg-app-card-soft text-xs text-app-muted-light"
            role="status"
          >
            <span className="size-4 animate-spin rounded-full border-2 border-app-muted border-t-app-yellow" />
            جاري تحميل باقات الاشتراك...
          </div>
        ) : (
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.plan_id}
            onChange={handlePlanChange}
            options={plans.map((plan) => ({
              value: String(plan.id),
              label: formatLocalizedName(plan.name),
            }))}
            placeholder="اختر الخطة"
            disabled={plans.length === 0 || Boolean(plansErrorMessage)}
            error={errors.plan_id}
          />
        )}
        {plansErrorMessage ? (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {plansErrorMessage}
          </span>
        ) : (
          !isPlansLoading &&
          plans.length === 0 && (
            <span className="mt-1.5 block text-xs text-app-muted-light" role="status">
              {selectedActivityTypeId
                ? "لا توجد باقات اشتراك متاحة لنوع النشاط المحدد"
                : "لا توجد باقات اشتراك متاحة حالياً"}
            </span>
          )
        )}
      </label>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-right text-sm text-app-muted-light">
          رقم العرض (اختياري)
          <input
            type="number"
            min="1"
            value={form.offer_id}
            onChange={(event) => updateField("offer_id", event.target.value)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${errors.offer_id ? "border-app-red" : "focus:border-app-yellow/70"}`}
          />
          {errors.offer_id && (
            <span className="mt-1 block text-xs text-app-red">{errors.offer_id}</span>
          )}
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          عدد الأشهر *
          <input
            type="number"
            min="1"
            value={form.months_count}
            onChange={(event) => handleEditMonthsChange(event.target.value)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${errors.months_count ? "border-app-red" : "focus:border-app-yellow/70"}`}
          />
          {errors.months_count && (
            <span className="mt-1 block text-xs text-app-red">{errors.months_count}</span>
          )}
        </label>
      </div>

      <SubscriptionDiscountFields
        form={form}
        originalTotal={originalAmounts.originalTotal}
        coachOriginal={originalAmounts.coachOriginal}
        branchOriginal={originalAmounts.branchOriginal}
        isPrivatePlan={isPrivatePlan}
        errors={errors}
        onToggle={toggleEditDiscount}
        onModeChange={changeEditDiscountMode}
        onFinalPriceChange={(value) => changeEditUnifiedDiscount(value, "price")}
        onPercentageChange={(value) => changeEditUnifiedDiscount(value, "percentage")}
        onCoachPriceChange={(value) => changeEditSplitDiscount("coach", value, "price")}
        onCoachPercentageChange={(value) => changeEditSplitDiscount("coach", value, "percentage")}
        onBranchPriceChange={(value) => changeEditSplitDiscount("branch", value, "price")}
        onBranchPercentageChange={(value) => changeEditSplitDiscount("branch", value, "percentage")}
        onReasonChange={(value) => updateField("discount_reason", value)}
      />

      {isPrivatePlan && (
        <div className="grid gap-3 rounded-xl border border-app-line bg-app-card-soft/40 p-4 sm:grid-cols-2">
          <label className="block text-right text-sm text-app-muted-light">
            دفعة الكوتش ({CURRENCY_SYMBOL})
            <input
              type="number"
              min="0"
              max={form.coach_final_price}
              step="0.01"
              value={form.coach_paid_amount}
              onChange={(event) => changeEditPrivatePaidAmount("coach", event.target.value)}
              disabled={!canEditPaidAmount}
              className="app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none disabled:cursor-not-allowed disabled:opacity-60 focus:border-app-yellow/70"
            />
          </label>
          <label className="block text-right text-sm text-app-muted-light">
            دفعة النادي ({CURRENCY_SYMBOL})
            <input
              type="number"
              min="0"
              max={form.branch_final_price}
              step="0.01"
              value={form.branch_paid_amount}
              onChange={(event) => changeEditPrivatePaidAmount("branch", event.target.value)}
              disabled={!canEditPaidAmount}
              className="app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none disabled:cursor-not-allowed disabled:opacity-60 focus:border-app-yellow/70"
            />
          </label>
        </div>
      )}

      {isPrivatePlan ? (
        <div className="rounded-xl border border-yellow-400/25 bg-yellow-400/[0.04] p-4">
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال الكوتش *
              <span className="ms-1 text-xs text-app-yellow">
                ({formatMoney(resolvedPlan?.coach_price)})
              </span>
              <input
                type="text"
                value={form.coach_receipt_number}
                onChange={(event) => updateField("coach_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors.coach_receipt_number)}
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors.coach_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال الكوتش"
                maxLength={100}
                required
              />
              {errors.coach_receipt_number && (
                <span className="mt-1.5 block text-xs text-app-red" role="alert">
                  {errors.coach_receipt_number}
                </span>
              )}
            </label>

            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال النادي *
              <span className="ms-1 text-xs text-app-yellow">
                ({formatMoney(resolvedPlan?.branch_price)})
              </span>
              <input
                type="text"
                value={form.branch_receipt_number}
                onChange={(event) => updateField("branch_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors.branch_receipt_number)}
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors.branch_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال النادي"
                maxLength={100}
                required
              />
              {errors.branch_receipt_number && (
                <span className="mt-1.5 block text-xs text-app-red" role="alert">
                  {errors.branch_receipt_number}
                </span>
              )}
            </label>
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          رقم الإيصال *
          <input
            type="text"
            value={form.receipt_number}
            onChange={(event) => updateField("receipt_number", event.target.value)}
            aria-invalid={Boolean(errors.receipt_number)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
              errors.receipt_number
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            placeholder="أدخل رقم الإيصال"
            maxLength={100}
            required
          />
          {errors.receipt_number && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.receipt_number}
            </span>
          )}
        </label>
      )}

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <DatePickerSmart
          label="تاريخ البداية *"
          value={form.start_date}
          onChange={(value) => updateField("start_date", value)}
          disabled={isDailyEntryPlan}
          allowClear={!isDailyEntryPlan}
          error={errors.start_date}
        />
        <DatePickerSmart
          label="تاريخ النهاية *"
          value={form.end_date}
          onChange={(value) => updateField("end_date", value)}
          disabled={isDailyEntryPlan}
          allowClear={!isDailyEntryPlan}
          error={errors.end_date}
        />
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-right text-sm text-app-muted-light">
          الحالة *
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.status}
            onChange={(value) => updateField("status", value)}
            options={SUBSCRIPTION_STATUS_OPTIONS.filter((option) => option.value !== "all")}
            error={errors.status}
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          المبلغ المدفوع ({CURRENCY_SYMBOL}) *
          <input
            type="number"
            min="0"
            step="0.01"
            value={form.paid_amount}
            onChange={(event) => updateField("paid_amount", event.target.value)}
            max={form.final_price}
            disabled={isPrivatePlan || !canEditPaidAmount}
            title={
              isPrivatePlan
                ? "إجمالي محسوب من دفعتي الكوتش والنادي"
                : !canEditPaidAmount
                  ? "تعديل المبلغ متاح للأدمن فقط"
                  : undefined
            }
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none disabled:cursor-not-allowed disabled:opacity-60 ${errors.paid_amount ? "border-app-red" : "focus:border-app-yellow/70"}`}
          />
          {errors.paid_amount && (
            <span className="mt-1 block text-xs text-app-red">{errors.paid_amount}</span>
          )}
          {isPrivatePlan ? (
            <span className="mt-1.5 block text-xs text-app-muted-light">
              الإجمالي محسوب تلقائياً من دفعتي الكوتش والنادي.
            </span>
          ) : !canEditPaidAmount ? (
            <span className="mt-1.5 block text-xs text-app-muted-light">
              تعديل المبلغ متاح للأدمن فقط.
            </span>
          ) : null}
        </label>
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        الملاحظات
        <textarea
          rows={4}
          value={form.notes}
          onChange={(event) => updateField("notes", event.target.value)}
          className={`app-input mt-2 min-h-24 w-full resize-y bg-app-card-soft px-3 py-3 text-right text-white outline-none ${errors.notes ? "border-app-red" : "focus:border-app-yellow/70"}`}
        />
        {errors.notes && <span className="mt-1 block text-xs text-app-red">{errors.notes}</span>}
      </label>

      <ModificationReasonField
        value={form.reason}
        onChange={(value) => updateField("reason", value)}
        error={errors.reason}
      />

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-sm text-app-red">
          {errorMessage}
        </p>
      )}

      <div className="flex gap-3 border-t border-app-line pt-4">
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1 text-black" loading={isLoading}>
          حفظ التعديلات
        </Button>
      </div>
    </form>
  );
}
