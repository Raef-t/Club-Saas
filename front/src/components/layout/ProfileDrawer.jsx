"use client";

import { useRef, useState } from "react";
import Drawer from "@/components/ui/Drawer";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import PhoneField from "@/components/forms/PhoneField";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import {
  useGetProfileQuery,
  useChangePasswordMutation,
  useLogoutMutation,
} from "@/lib/api/authApi";
import { authApi } from "@/lib/api/authApi";
import {
  useGetStaffMemberQuery,
  useUpdateStaffMemberMutation,
  useUpdateStaffPhotoMutation,
} from "@/lib/api/staffApi";
import { clearAuthStorage } from "@/lib/authStorage";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import { EyeIcon, EyeOffIcon, PencilIcon } from "@/components/icons/Icons";
import { useDispatch } from "react-redux";
import {
  createStaffProfileUpdateBody,
  getStaffRecord,
  splitStaffName,
  resolveStaffPhotoUrl,
} from "@/app/management/staff/staffUtils";

const GENDER_OPTIONS = [
  { value: "male", label: "ذكر" },
  { value: "female", label: "أنثى" },
];

export default function ProfileDrawer({ open, onClose }) {
  const toast = useToast();
  const dispatch = useDispatch();
  const { data: profileData, isLoading: loadingProfile } = useGetProfileQuery(undefined, {
    skip: !open,
  });
  const authUser = profileData?.data || {};
  const staffId = authUser.staff_id || authUser.staff?.id || authUser.id;
  const { currentData: staffProfileResponse, isFetching: isFetchingStaffProfile } =
    useGetStaffMemberQuery(staffId, {
      skip: !open || !staffId,
    });
  const user = getStaffRecord(staffProfileResponse) || authUser;
  const [changePassword, { isLoading: isChanging }] = useChangePasswordMutation();
  const [updateStaffMember, { isLoading: isUpdating }] = useUpdateStaffMemberMutation();
  const [updateStaffPhoto, { isLoading: isUpdatingPhoto }] = useUpdateStaffPhotoMutation();
  const [logout] = useLogoutMutation();

  const [form, setForm] = useState({
    current_password: "",
    new_password: "",
    new_password_confirmation: "",
  });

  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  const [formError, setFormError] = useState("");
  const [fieldErrors, setFieldErrors] = useState({});

  // --- Profile edit state ---
  const [editMode, setEditMode] = useState(false);
  const [editForm, setEditForm] = useState({});
  const [editError, setEditError] = useState("");
  const [editFieldErrors, setEditFieldErrors] = useState({});
  const [photoPreview, setPhotoPreview] = useState(null);
  const fileInputRef = useRef(null);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (fieldErrors[field]) {
      setFieldErrors((current) => ({ ...current, [field]: null }));
    }
    setFormError("");
  }

  function updateEditField(field, value) {
    setEditForm((current) => ({ ...current, [field]: value }));
    if (editFieldErrors[field]) {
      setEditFieldErrors((current) => ({ ...current, [field]: null }));
    }
    setEditError("");
  }

  function startEdit() {
    const person = user.person || {};
    const { firstName, lastName } = splitStaffName(person);

    setEditForm({
      first_name: firstName,
      last_name: lastName,
      country_code: person.country_code || "+963",
      phone_number: person.phone_number || "",
      gender: person.gender || "male",
      photo: null,
      reason: "",
    });
    setPhotoPreview(null);
    setEditError("");
    setEditFieldErrors({});
    setEditMode(true);
  }

  function cancelEdit() {
    setEditMode(false);
    setEditForm({});
    setPhotoPreview(null);
    setEditError("");
    setEditFieldErrors({});
  }

  function handlePhotoSelect(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file type and size
    if (!file.type.startsWith("image/")) {
      setEditError("يرجى اختيار ملف صورة صحيح.");
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      setEditError("حجم الصورة يجب أن يكون أقل من 5 ميغابايت.");
      return;
    }

    updateEditField("photo", file);
    setPhotoPreview(URL.createObjectURL(file));
  }

  async function handleProfileSubmit(e) {
    e.preventDefault();
    setEditError("");
    setEditFieldErrors({});

    // Validation
    const errors = {};
    if (!editForm.first_name?.trim()) errors.first_name = "الاسم الأول مطلوب";
    if (!editForm.last_name?.trim()) errors.last_name = "اسم العائلة مطلوب";
    if (!editForm.phone_number?.trim()) errors.phone_number = "رقم الهاتف مطلوب";
    if (!editForm.reason?.trim()) errors.reason = "سبب التعديل مطلوب";

    if (Object.keys(errors).length > 0) {
      setEditFieldErrors(errors);
      return;
    }

    if (!user.role || !user.employment_type || !user.work_status) {
      setEditError(
        "تعذر تحميل بيانات التوظيف المطلوبة للتعديل. أغلق الملف الشخصي ثم أعد فتحه وحاول مجددًا.",
      );
      return;
    }

    try {
      await updateStaffMember({
        id: user.id,
        body: createStaffProfileUpdateBody(user, editForm),
      }).unwrap();

      if (editForm.photo instanceof File) {
        const photoFormData = new FormData();
        photoFormData.append("photo", editForm.photo);
        await updateStaffPhoto({ id: user.id, body: photoFormData }).unwrap();
      }

      // Invalidate the profile cache to refetch fresh data
      dispatch(authApi.util.invalidateTags(["Profile"]));

      toast.success("تم تحديث بيانات الملف الشخصي بنجاح.");
      setEditMode(false);
      setPhotoPreview(null);
    } catch (err) {
      setEditError(
        getApiErrorMessage(err, "تعذر تحديث البيانات. تحقق من البيانات وحاول مرة أخرى."),
      );
    }
  }

  async function handlePasswordSubmit(e) {
    e.preventDefault();
    setFormError("");
    setFieldErrors({});

    const errors = {};
    if (!form.current_password) errors.current_password = "كلمة المرور الحالية مطلوبة";
    if (!form.new_password) errors.new_password = "كلمة المرور الجديدة مطلوبة";
    if (!form.new_password_confirmation)
      errors.new_password_confirmation = "تأكيد كلمة المرور مطلوبة";

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      return;
    }

    if (form.new_password.length < 6) {
      setFormError("يجب أن تكون كلمة المرور الجديدة 6 أحرف على الأقل.");
      return;
    }

    if (form.new_password !== form.new_password_confirmation) {
      setFormError("كلمة المرور الجديدة غير مطابقة لتأكيد كلمة المرور.");
      return;
    }

    try {
      await changePassword({
        current_password: form.current_password,
        new_password: form.new_password,
        new_password_confirmation: form.new_password_confirmation,
      }).unwrap();

      toast.success("تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول بكلمة المرور الجديدة.");
      try {
        await logout().unwrap();
      } catch {
        // Ignore error if session was already terminated by backend
      }
      clearAuthStorage();
      window.location.replace("/login");
    } catch (err) {
      setFormError(
        err?.data?.message || "تعذر تغيير كلمة المرور. تأكد من صحة البيانات وحاول مرة أخرى.",
      );
    }
  }

  const person = user.person || {};
  const fullName = person.full_name || user.username || "مستخدم تكنوجيم";
  // const email = person.email || "";
  const phone = person.phone_number || person.contacts?.[0]?.phone_number || "";
  const gender = person.gender === "male" ? "ذكر" : person.gender === "female" ? "أنثى" : "-";
  const avatarLetter = fullName.charAt(0).toUpperCase();

  const currentPhotoUrl = resolveStaffPhotoUrl(person.photo_url);

  return (
    <Drawer
      open={open}
      onClose={() => {
        cancelEdit();
        onClose();
      }}
      title="الملف الشخصي"
      subtitle="بيانات المستخدم الحالي وتحديث كلمة المرور"
      side="left"
    >
      <div className="space-y-6" dir="rtl">
        {/* User Card Info */}
        <div className="flex flex-col items-center gap-3 border-b border-app-line pb-6">
          <div className="relative">
            <div className="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-app-yellow text-3xl font-bold text-black uppercase shadow-lg">
              {editMode && photoPreview ? (
                <img
                  src={photoPreview}
                  className="h-full w-full object-cover"
                  alt="معاينة الصورة"
                />
              ) : currentPhotoUrl ? (
                <img
                  src={currentPhotoUrl}
                  className="h-full w-full object-cover"
                  alt="Profile Avatar"
                />
              ) : (
                avatarLetter
              )}
            </div>
            {editMode && (
              <>
                <button
                  type="button"
                  onClick={() => fileInputRef.current?.click()}
                  className="absolute -bottom-1 -end-1 grid size-8 place-items-center rounded-full bg-app-yellow text-black shadow-lg transition hover:scale-110 active:scale-95"
                  title="تغيير الصورة"
                >
                  <PencilIcon className="size-4" />
                </button>
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  onChange={handlePhotoSelect}
                  className="hidden"
                />
              </>
            )}
          </div>
          {!editMode && (
            <>
              <h2 className="text-lg font-semibold text-app-text">{fullName}</h2>
              <span className="rounded-full bg-app-yellow/10 px-3 py-1 text-xs text-app-yellow font-medium">
                {person.type === "staff" ? "مدير النظام" : "موظف"}
              </span>
            </>
          )}
        </div>

        {/* Detailed profile properties / Edit form */}
        {loadingProfile ? (
          <div className="py-10 text-center text-sm text-app-muted-light">
            جاري تحميل البيانات...
          </div>
        ) : editMode ? (
          /* ========= EDIT MODE ========= */
          <form noValidate onSubmit={handleProfileSubmit} className="space-y-4">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <label className="block text-right text-sm text-app-muted-light">
                الاسم الأول *
                <input
                  type="text"
                  value={editForm.first_name || ""}
                  onChange={(e) => updateEditField("first_name", e.target.value)}
                  placeholder="الاسم الأول"
                  className={`mt-2 h-11 w-full rounded-xl border bg-app-card-soft px-4 text-right text-app-text outline-none transition ${
                    editFieldErrors.first_name
                      ? "border-app-red focus:border-app-red"
                      : "border-app-line focus:border-app-yellow"
                  }`}
                />
                {editFieldErrors.first_name && (
                  <span className="text-app-red text-xs mt-1 block">
                    {editFieldErrors.first_name}
                  </span>
                )}
              </label>
              <label className="block text-right text-sm text-app-muted-light">
                اسم العائلة *
                <input
                  type="text"
                  value={editForm.last_name || ""}
                  onChange={(e) => updateEditField("last_name", e.target.value)}
                  placeholder="اسم العائلة"
                  className={`mt-2 h-11 w-full rounded-xl border bg-app-card-soft px-4 text-right text-app-text outline-none transition ${
                    editFieldErrors.last_name
                      ? "border-app-red focus:border-app-red"
                      : "border-app-line focus:border-app-yellow"
                  }`}
                />
                {editFieldErrors.last_name && (
                  <span className="text-app-red text-xs mt-1 block">
                    {editFieldErrors.last_name}
                  </span>
                )}
              </label>
            </div>

            <PhoneField
              label="رقم الهاتف"
              phoneValue={editForm.phone_number}
              onPhoneChange={(value) => updateEditField("phone_number", value)}
              codeValue={editForm.country_code}
              onCodeChange={(value) => updateEditField("country_code", value)}
              required
              className="w-full text-right"
              error={editFieldErrors.phone_number}
            />

            <label className="block text-right text-sm text-app-muted-light">
              الجنس
              <Dropdown
                className="mt-2 text-app-text"
                buttonClassName="h-11 bg-app-card-soft"
                value={editForm.gender}
                onChange={(value) => updateEditField("gender", value)}
                options={GENDER_OPTIONS}
              />
            </label>

            <ModificationReasonField
              value={editForm.reason}
              onChange={(value) => updateEditField("reason", value)}
              error={editFieldErrors.reason}
            />

            {editError && (
              <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
                {editError}
              </p>
            )}

            <div className="flex flex-col-reverse gap-3 sm:flex-row">
              <Button
                type="button"
                tone="outline"
                className="flex-1"
                onClick={cancelEdit}
                disabled={isUpdating || isUpdatingPhoto}
              >
                إلغاء
              </Button>
              <Button
                type="submit"
                className="flex-1 text-black"
                loading={isUpdating || isUpdatingPhoto}
                loadingLabel="جاري الحفظ"
              >
                حفظ التعديلات
              </Button>
            </div>
          </form>
        ) : (
          /* ========= VIEW MODE ========= */
          <>
            <div className="space-y-3.5">
              <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
                <span className="text-app-muted-light font-medium">اسم المستخدم</span>
                <span className="text-app-text">{user.username || "-"}</span>
              </div>
              {/* <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
                <span className="text-app-muted-light font-medium">
                  البريد الإلكتروني
                </span>
                <span className="text-app-text">{email || "-"}</span>
              </div> */}
              <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
                <span className="text-app-muted-light font-medium">رقم الهاتف</span>
                <span className="text-app-text" dir="ltr">
                  {phone || "-"}
                </span>
              </div>
              <div className="flex items-center justify-between rounded-xl bg-app-card-soft/40 px-4 py-3 text-sm">
                <span className="text-app-muted-light font-medium">الجنس</span>
                <span className="text-app-text">{gender}</span>
              </div>
            </div>

            <Button
              type="button"
              tone="outline"
              className="w-full"
              onClick={startEdit}
              disabled={isFetchingStaffProfile}
            >
              تعديل البيانات الشخصية
            </Button>
          </>
        )}

        {/* Change Password Form */}
        {!editMode && (
          <div className="border-t border-app-line pt-6">
            <h3 className="text-sm font-semibold text-app-text mb-4">تغيير كلمة المرور</h3>

            <form noValidate onSubmit={handlePasswordSubmit} className="space-y-4">
              {/* current_password */}
              <label className="block text-right text-sm text-app-muted-light relative">
                كلمة المرور الحالية
                <div className="relative mt-2">
                  <input
                    value={form.current_password}
                    type={showCurrent ? "text" : "password"}
                    dir="ltr"
                    onChange={(e) => updateField("current_password", e.target.value)}
                    className={`h-11 w-full rounded-xl border bg-app-card-soft ps-4 pe-12 text-right text-app-text outline-none transition ${
                      fieldErrors.current_password
                        ? "border-app-red focus:border-app-red"
                        : "border-app-line focus:border-app-yellow"
                    }`}
                    placeholder="أدخل كلمة المرور الحالية"
                  />
                  <button
                    type="button"
                    onClick={() => setShowCurrent(!showCurrent)}
                    className="absolute end-4 top-1/2 -translate-y-1/2 text-app-muted-light transition hover:text-app-text"
                  >
                    {showCurrent ? (
                      <EyeOffIcon className="size-4" />
                    ) : (
                      <EyeIcon className="size-4" />
                    )}
                  </button>
                </div>
                {fieldErrors.current_password && (
                  <span className="text-app-red text-xs mt-1 block">
                    {fieldErrors.current_password}
                  </span>
                )}
              </label>

              {/* new_password */}
              <label className="block text-right text-sm text-app-muted-light relative">
                كلمة المرور الجديدة
                <div className="relative mt-2">
                  <input
                    value={form.new_password}
                    type={showNew ? "text" : "password"}
                    dir="ltr"
                    onChange={(e) => updateField("new_password", e.target.value)}
                    className={`h-11 w-full rounded-xl border bg-app-card-soft ps-4 pe-12 text-right text-app-text outline-none transition ${
                      fieldErrors.new_password
                        ? "border-app-red focus:border-app-red"
                        : "border-app-line focus:border-app-yellow"
                    }`}
                    placeholder="أدخل كلمة المرور الجديدة"
                  />
                  <button
                    type="button"
                    onClick={() => setShowNew(!showNew)}
                    className="absolute end-4 top-1/2 -translate-y-1/2 text-app-muted-light transition hover:text-app-text"
                  >
                    {showNew ? <EyeOffIcon className="size-4" /> : <EyeIcon className="size-4" />}
                  </button>
                </div>
                {fieldErrors.new_password && (
                  <span className="text-app-red text-xs mt-1 block">
                    {fieldErrors.new_password}
                  </span>
                )}
              </label>

              {/* new_password_confirmation */}
              <label className="block text-right text-sm text-app-muted-light relative">
                تأكيد كلمة المرور الجديدة
                <div className="relative mt-2">
                  <input
                    value={form.new_password_confirmation}
                    type={showConfirm ? "text" : "password"}
                    dir="ltr"
                    onChange={(e) => updateField("new_password_confirmation", e.target.value)}
                    className={`h-11 w-full rounded-xl border bg-app-card-soft ps-4 pe-12 text-right text-app-text outline-none transition ${
                      fieldErrors.new_password_confirmation
                        ? "border-app-red focus:border-app-red"
                        : "border-app-line focus:border-app-yellow"
                    }`}
                    placeholder="أعد كتابة كلمة المرور الجديدة"
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirm(!showConfirm)}
                    className="absolute end-4 top-1/2 -translate-y-1/2 text-app-muted-light transition hover:text-app-text"
                  >
                    {showConfirm ? (
                      <EyeOffIcon className="size-4" />
                    ) : (
                      <EyeIcon className="size-4" />
                    )}
                  </button>
                </div>
                {fieldErrors.new_password_confirmation && (
                  <span className="text-app-red text-xs mt-1 block">
                    {fieldErrors.new_password_confirmation}
                  </span>
                )}
              </label>

              {formError && (
                <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
                  {formError}
                </p>
              )}

              <Button
                className="h-11 w-full text-sm font-semibold"
                type="submit"
                loading={isChanging}
                loadingLabel="جاري التحديث"
              >
                تغيير كلمة المرور
              </Button>
            </form>
          </div>
        )}
      </div>
    </Drawer>
  );
}
