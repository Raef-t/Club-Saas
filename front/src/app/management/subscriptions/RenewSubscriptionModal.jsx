"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import Modal from "@/components/ui/Modal";
import { CURRENCY_SYMBOL, formatLocalizedName, formatMoney } from "@/lib/utils";
import { subscriptionRenewalSchema } from "@/lib/validations/subscriptionsSchema";
import {
  getSubscriptionSplitPaymentAmounts,
  isPrivateSubscriptionPlan,
  parseSubscriptionAmount,
} from "./subscriptionUtils";

function getPlanPrice(plan, subscription) {
  const price = plan?.base_price ?? plan?.price ?? subscription?.total_amount ?? 0;
  const numericPrice = Number(price);
  return Number.isFinite(numericPrice) ? numericPrice : 0;
}

function getPlanName(plan) {
  return formatLocalizedName(plan?.name) || plan?.name || `خطة #${plan?.id}`;
}

/** Collects the payment details required by POST /player-subscriptions/{id}/renew. */
export default function RenewSubscriptionModal({
  open,
  subscription,
  plans = [],
  isPlansLoading = false,
  plansErrorMessage = "",
  onClose,
  onSubmit,
  isLoading = false,
  errorMessage = "",
}) {
  const currentPlan = subscription?.plan || null;
  const availablePlans = useMemo(() => {
    const uniquePlans = new Map();

    if (currentPlan?.id != null) uniquePlans.set(String(currentPlan.id), currentPlan);
    plans.forEach((plan) => {
      if (plan?.id != null) uniquePlans.set(String(plan.id), plan);
    });

    return [...uniquePlans.values()];
  }, [currentPlan, plans]);

  const [form, setForm] = useState({
    plan_id: "",
    paid_amount: "",
    receipt_number: "",
    branch_paid_amount: "",
    coach_paid_amount: "",
    branch_receipt_number: "",
    coach_receipt_number: "",
    is_private_plan: false,
  });
  const [errors, setErrors] = useState({});

  const selectedPlan =
    availablePlans.find((plan) => String(plan.id) === form.plan_id) || currentPlan;
  const isPrivatePlan = isPrivateSubscriptionPlan(selectedPlan);
  const selectedPlanPrice = getPlanPrice(selectedPlan, subscription);

  useEffect(() => {
    if (!open || !subscription) return;

    const plan = subscription.plan || null;
    const isPrivate = isPrivateSubscriptionPlan(plan);
    const split = getSubscriptionSplitPaymentAmounts(subscription, plan);
    const price = getPlanPrice(plan, subscription);
    const branchAmount = split.branchPaidAmount;
    const coachAmount = split.coachPaidAmount;
    const total = isPrivate ? Number((branchAmount + coachAmount).toFixed(2)) : price;

    setForm({
      plan_id: plan?.id == null ? "" : String(plan.id),
      paid_amount: String(total),
      receipt_number: "",
      branch_paid_amount: isPrivate ? String(branchAmount) : "",
      coach_paid_amount: isPrivate ? String(coachAmount) : "",
      branch_receipt_number: "",
      coach_receipt_number: "",
      is_private_plan: isPrivate,
    });
    setErrors({});
  }, [open, subscription]);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handlePlanChange(planId) {
    const plan = availablePlans.find((item) => String(item.id) === planId);
    const isPrivate = isPrivateSubscriptionPlan(plan);
    const price = getPlanPrice(plan, subscription);
    const branchPrice = parseSubscriptionAmount(plan?.branch_price);
    const coachPrice = parseSubscriptionAmount(plan?.coach_price);
    const total = isPrivate ? Number((branchPrice + coachPrice).toFixed(2)) : price;

    setForm((current) => ({
      ...current,
      plan_id: planId,
      paid_amount: String(total),
      branch_paid_amount: isPrivate ? String(branchPrice) : "",
      coach_paid_amount: isPrivate ? String(coachPrice) : "",
      branch_receipt_number: "",
      coach_receipt_number: "",
      is_private_plan: isPrivate,
    }));
    setErrors((current) => ({
      ...current,
      plan_id: null,
      paid_amount: null,
      receipt_number: null,
      branch_receipt_number: null,
      coach_receipt_number: null,
      branch_paid_amount: null,
      coach_paid_amount: null,
    }));
  }

  function handlePrivatePaidChange(side, value) {
    setForm((current) => {
      const branch = Number(side === "branch" ? value : current.branch_paid_amount) || 0;
      const coach = Number(side === "coach" ? value : current.coach_paid_amount) || 0;
      return {
        ...current,
        [`${side}_paid_amount`]: value,
        paid_amount: String(Number((branch + coach).toFixed(2))),
      };
    });
    if (errors[`${side}_paid_amount`]) {
      setErrors((current) => ({ ...current, [`${side}_paid_amount`]: null }));
    }
  }

  async function handleSubmit(event) {
    event.preventDefault();
    const isPrivate = isPrivateSubscriptionPlan(selectedPlan);
    const branchPaid = Number(form.branch_paid_amount) || 0;
    const coachPaid = Number(form.coach_paid_amount) || 0;
    const resolvedPaidAmount = isPrivate
      ? Number((branchPaid + coachPaid).toFixed(2))
      : form.paid_amount;

    const validationPayload = {
      ...form,
      paid_amount: resolvedPaidAmount,
      is_private_plan: isPrivate,
      ...(isPrivate
        ? {
            branch_paid_amount: branchPaid,
            coach_paid_amount: coachPaid,
          }
        : {}),
    };

    const result = subscriptionRenewalSchema.safeParse(validationPayload);

    if (!result.success) {
      const nextErrors = {};
      result.error.issues.forEach((issue) => {
        nextErrors[issue.path[0]] = issue.message;
      });
      setErrors(nextErrors);
      return;
    }

    setErrors({});
    await onSubmit?.({
      ...result.data,
      payment_method: "cash",
    });
  }

  const memberName = subscription?.member?.person?.full_name;

  return (
    <Modal
      open={open}
      onClose={isLoading ? undefined : onClose}
      title="تجديد الاشتراك"
      subtitle={memberName || (subscription?.id ? `رقم الاشتراك ${subscription.id}` : "")}
      className="max-w-xl"
    >
      <form className="space-y-5" noValidate onSubmit={handleSubmit}>
        <label className="block text-sm text-app-muted-light">
          اسم الاشتراك السابق مع إمكانية التعديل *
          <Dropdown
            ariaLabel="اسم الاشتراك السابق مع إمكانية التعديل"
            className="mt-2 text-app-text"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.plan_id}
            onChange={handlePlanChange}
            disabled={isLoading || (isPlansLoading && availablePlans.length === 0)}
            options={availablePlans.map((plan) => ({
              value: String(plan.id),
              label: getPlanName(plan),
            }))}
            placeholder="اختر خطة الاشتراك"
            error={errors.plan_id}
            searchable
            searchPlaceholder="ابحث عن خطة اشتراك..."
          />
          {plansErrorMessage && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {plansErrorMessage}
            </span>
          )}
        </label>

        <div className="rounded-xl border border-app-yellow/25 bg-app-yellow-soft/40 p-4">
          <p className="text-xs text-app-muted-light">المبلغ الموافق للخطة / الفعالية</p>
          <p className="mt-1 text-xl font-semibold text-app-yellow">
            {formatMoney(selectedPlanPrice)}
          </p>
        </div>

        {isPrivatePlan ? (
          <div className="space-y-4 rounded-xl border border-yellow-400/30 bg-yellow-400/[0.03] p-4">
            <div className="flex flex-col gap-1 border-b border-app-line pb-2.5 sm:flex-row sm:items-center sm:justify-between">
              <span className="text-xs font-semibold text-app-yellow">
                مدفوعات وإيصالات الاشتراك الخاص (أجهزة خاص)
              </span>
              <span className="text-[11px] text-app-muted-light">
                أتعاب الكوتش تسجل كدفعة منفصلة ولا تدخل صندوق النادي
              </span>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              {/* مدفوعات وإيصال النادي */}
              <div className="space-y-3 rounded-xl border border-app-line/80 bg-app-card-soft/50 p-3.5">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-medium text-app-text">
                    حصة النادي (تدخل الصندوق)
                  </span>
                  {selectedPlan && (
                    <span className="text-xs font-semibold text-app-green">
                      {formatMoney(selectedPlan?.branch_price)}
                    </span>
                  )}
                </div>

                <label className="block text-right text-xs text-app-muted-light">
                  مدفوع النادي (يدخل الصندوق) ({CURRENCY_SYMBOL}) *
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.branch_paid_amount}
                    onChange={(event) => handlePrivatePaidChange("branch", event.target.value)}
                    disabled={isLoading}
                    className="app-input mt-1.5 h-10 w-full bg-app-card-soft px-3 text-right text-app-text outline-none focus:border-app-yellow/70"
                    required
                  />
                </label>

                <label className="block text-right text-xs text-app-muted-light">
                  رقم إيصال النادي *
                  <input
                    type="text"
                    value={form.branch_receipt_number}
                    onChange={(event) => updateField("branch_receipt_number", event.target.value)}
                    maxLength={100}
                    disabled={isLoading}
                    aria-invalid={Boolean(errors.branch_receipt_number)}
                    className={`app-input mt-1.5 h-10 w-full bg-app-card-soft px-3 text-right text-app-text outline-none ${
                      errors.branch_receipt_number ? "border-app-red" : "focus:border-app-yellow/70"
                    }`}
                    placeholder="أدخل رقم إيصال النادي"
                    required
                  />
                  {errors.branch_receipt_number && (
                    <span className="mt-1 block text-xs text-app-red" role="alert">
                      {errors.branch_receipt_number}
                    </span>
                  )}
                </label>
              </div>

              {/* مدفوعات وإيصال الكوتش */}
              <div className="space-y-3 rounded-xl border border-app-line/80 bg-app-card-soft/50 p-3.5">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-medium text-app-text">
                    حصة الكوتش (أتعاب خاصة لا تدخل الصندوق)
                  </span>
                  {selectedPlan && (
                    <span className="text-xs font-semibold text-app-yellow">
                      {formatMoney(selectedPlan?.coach_price)}
                    </span>
                  )}
                </div>

                <label className="block text-right text-xs text-app-muted-light">
                  مدفوع الكوتش (أتعاب خاصة لا تدخل الصندوق) ({CURRENCY_SYMBOL}) *
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.coach_paid_amount}
                    onChange={(event) => handlePrivatePaidChange("coach", event.target.value)}
                    disabled={isLoading}
                    className="app-input mt-1.5 h-10 w-full bg-app-card-soft px-3 text-right text-app-text outline-none focus:border-app-yellow/70"
                    required
                  />
                </label>

                <label className="block text-right text-xs text-app-muted-light">
                  رقم إيصال الكوتش *
                  <input
                    type="text"
                    value={form.coach_receipt_number}
                    onChange={(event) => updateField("coach_receipt_number", event.target.value)}
                    maxLength={100}
                    disabled={isLoading}
                    aria-invalid={Boolean(errors.coach_receipt_number)}
                    className={`app-input mt-1.5 h-10 w-full bg-app-card-soft px-3 text-right text-app-text outline-none ${
                      errors.coach_receipt_number ? "border-app-red" : "focus:border-app-yellow/70"
                    }`}
                    placeholder="أدخل رقم إيصال الكوتش"
                    required
                  />
                  {errors.coach_receipt_number && (
                    <span className="mt-1 block text-xs text-app-red" role="alert">
                      {errors.coach_receipt_number}
                    </span>
                  )}
                </label>
              </div>
            </div>

            <div className="flex items-center justify-between rounded-lg bg-black/30 px-4 py-2.5">
              <span className="text-xs text-app-muted-light">إجمالي المبلغ المحتسب تلقائياً:</span>
              <span className="text-sm font-semibold text-app-green">
                {formatMoney(form.paid_amount)}
              </span>
            </div>
          </div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="block text-sm text-app-muted-light">
              رقم الإيصال *
              <input
                type="text"
                value={form.receipt_number}
                onChange={(event) => updateField("receipt_number", event.target.value)}
                maxLength={100}
                disabled={isLoading}
                aria-invalid={Boolean(errors.receipt_number)}
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-app-text outline-none ${
                  errors.receipt_number ? "border-app-red" : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم الإيصال"
                required
              />
              {errors.receipt_number && (
                <span className="mt-1.5 block text-xs text-app-red" role="alert">
                  {errors.receipt_number}
                </span>
              )}
            </label>

            <label className="block text-sm text-app-muted-light">
              الكمية المدفوعة ({CURRENCY_SYMBOL}) *
              <input
                type="number"
                min="0"
                step="any"
                value={form.paid_amount}
                onChange={(event) => updateField("paid_amount", event.target.value)}
                disabled={isLoading}
                aria-invalid={Boolean(errors.paid_amount)}
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-app-text outline-none ${
                  errors.paid_amount ? "border-app-red" : "focus:border-app-yellow/70"
                }`}
                placeholder="0"
                required
              />
              {errors.paid_amount && (
                <span className="mt-1.5 block text-xs text-app-red" role="alert">
                  {errors.paid_amount}
                </span>
              )}
            </label>
          </div>
        )}

        {errorMessage && (
          <p
            className="rounded-xl border border-app-red/30 bg-app-red/10 px-4 py-3 text-sm text-app-red"
            role="alert"
          >
            {errorMessage}
          </p>
        )}

        <div className="flex justify-end gap-3 border-t border-app-line pt-4">
          <Button type="button" tone="outline" onClick={onClose} disabled={isLoading}>
            إلغاء
          </Button>
          <Button type="submit" loading={isLoading} loadingLabel="جاري تجديد الاشتراك">
            تأكيد التجديد
          </Button>
        </div>
      </form>
    </Modal>
  );
}

