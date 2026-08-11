"use client";

import { useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import AccountCredentialsDialog from "@/components/ui/AccountCredentialsDialog";
import FormStepper from "@/components/forms/FormStepper";
import { FormCard, UploadBox } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { MemberForm, memberInitialForm } from "../MemberForm";
import { useMembers } from "../useMembers";
import { SubscriptionCreateForm } from "@/app/management/subscriptions/SubscriptionForm";
import { useCreateSubscription } from "@/app/management/subscriptions/useCreateSubscription";
import { extractCreatedAccount } from "@/lib/generatedAccount";

const MEMBER_FORM_ID = "create-member-form";
const SUBSCRIPTION_FORM_ID = "create-subscription-stepper-form";

const STEPPER_STEPS = [{ label: "بيانات اللاعب" }, { label: "إضافة اشتراك" }];

export default function MembersCreateClient({ initialSubscriptionData }) {
  const router = useRouter();
  const { selectedBranchId } = useManagementBranch();
  const searchParams = useSearchParams();
  const mode = searchParams.get("mode");
  const editId = searchParams.get("id");
  const isEdit = mode === "edit" && editId;

  /* ── Step state (create mode only) ─────────────────────────── */
  const [step, setStep] = useState(0);
  const [createdMemberId, setCreatedMemberId] = useState(null);
  const [createdCredentials, setCreatedCredentials] = useState(null);

  /* ── Members hook ──────────────────────────────────────────── */
  const {
    branches,
    plans,
    formError,
    isCreating,
    isUpdating,
    handleCreate,
    handleUpdate,
    getEditInitialValues,
  } = useMembers({ selectedMemberId: isEdit ? Number(editId) : null });

  /* ── Subscription hook (only used in step 2) ───────────────── */
  const {
    members: subMembers,
    plans: subPlans,
    activities: subActivities,
    coaches: subCoaches,
    formError: subFormError,
    isCreating: isCreatingSub,
    handleCreateSubscription,
  } = useCreateSubscription({ initialData: initialSubscriptionData });

  const editInitialValues = isEdit ? getEditInitialValues() : null;

  /* ── Step 1: submit member ─────────────────────────────────── */
  async function submitMember(values) {
    if (isEdit) {
      const ok = await handleUpdate(values);
      if (ok) router.push("/management/members");
    } else {
      const response = await handleCreate(values);
      if (response) {
        const account = extractCreatedAccount(response, { entityKeys: ["member"] });
        setCreatedMemberId(account.id);

        if (account.username || account.password) {
          setCreatedCredentials(account);
        } else {
          setStep(1);
        }
      }
    }
  }

  /* ── Step 2: submit subscription ───────────────────────────── */
  async function submitSubscription(values) {
    const ok = await handleCreateSubscription(values);
    if (ok) {
      router.push("/management/members");
    }
  }

  /* ── Skip subscription ─────────────────────────────────────── */
  function handleSkip() {
    router.push("/management/members");
  }

  function continueAfterCredentials() {
    setCreatedCredentials(null);
    setStep(1);
  }

  /* ── Titles ────────────────────────────────────────────────── */
  const pageTitle = isEdit ? "تعديل عضو" : step === 0 ? "إضافة عضو" : "إضافة اشتراك";

  const pageSubtitle = isEdit
    ? "إدارة الأعضاء > تعديل عضو"
    : step === 0
      ? "إدارة الأعضاء > إضافة عضو"
      : "إدارة الأعضاء > إضافة عضو > إضافة اشتراك";

  const activeFormId = isEdit || step === 0 ? MEMBER_FORM_ID : SUBSCRIPTION_FORM_ID;

  return (
    <>
      <ManagementCreatePage
        title={pageTitle}
        subtitle={pageSubtitle}
        formId={activeFormId}
        backHref="/management/members"
        isSubmitting={isEdit ? isUpdating : step === 0 ? isCreating : isCreatingSub}
        submitLabel={isEdit ? "حفظ التعديل" : step === 0 ? "التالي" : "حفظ الاشتراك"}
      >
        {/* ── Stepper bar (create mode only) ───────────────────── */}
        {!isEdit && (
          <div className="mb-4">
            <FormStepper steps={STEPPER_STEPS} currentStep={step} />
          </div>
        )}

        {/* ── Step 1: Member data ──────────────────────────────── */}
        {(isEdit || step === 0) && (
          <div className={!isEdit ? "form-stepper-body" : undefined} key="step-member">
            <div className="entry-form-side-layout">
              <FormCard title="البيانات الشخصية" className="entry-form-card p-5">
                {isEdit && !editInitialValues ? (
                  <p className="py-8 text-center text-sm text-app-muted-light">
                    جاري تحميل بيانات العضو...
                  </p>
                ) : (
                  <MemberForm
                    key={isEdit ? `member-edit-${editId}` : `member-create-${selectedBranchId}`}
                    formId={MEMBER_FORM_ID}
                    mode={isEdit ? "edit" : "create"}
                    branches={branches}
                    plans={plans}
                    initialValues={editInitialValues || memberInitialForm}
                    onSubmit={submitMember}
                    onCancel={() => router.push("/management/members")}
                    isLoading={isEdit ? isUpdating : isCreating}
                    errorMessage={formError}
                    submitLabel={isEdit ? undefined : "التالي ←"}
                  />
                )}
              </FormCard>
              <UploadBox compact className="entry-form-card" maxSizeMB={2} />
            </div>
          </div>
        )}

        {/* ── Step 2: Subscription ─────────────────────────────── */}
        {!isEdit && step === 1 && (
          <div className="form-stepper-body" key="step-subscription">
            <FormCard title="تفاصيل الاشتراك" className="entry-form-card p-5">
              <SubscriptionCreateForm
                key={`sub-for-${createdMemberId}`}
                formId={SUBSCRIPTION_FORM_ID}
                initialMemberId={String(createdMemberId)}
                lockMemberId
                members={subMembers}
                plans={subPlans}
                activities={subActivities}
                coaches={subCoaches}
                onSubmit={submitSubscription}
                onCancel={handleSkip}
                isLoading={isCreatingSub}
                errorMessage={subFormError}
                submitLabel="حفظ الاشتراك"
                cancelLabel="تخطي ←"
                showAddAnother={false}
              />
            </FormCard>
          </div>
        )}
      </ManagementCreatePage>

      <AccountCredentialsDialog
        credentials={createdCredentials}
        entityLabel="اللاعب"
        closeLabel="متابعة إلى إضافة الاشتراك"
        onClose={continueAfterCredentials}
      />
    </>
  );
}
