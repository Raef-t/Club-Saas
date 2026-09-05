"use client";

import { useEffect, useState } from "react";
import Drawer from "@/components/ui/Drawer";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import {
  useGetProfileQuery,
  useUpdateProfileMutation,
  useChangePasswordMutation,
  useLogoutMutation,
  createProfileUpdateBody,
} from "@/lib/api/authApi";
import { clearAuthStorage } from "@/lib/authStorage";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage, getApiFieldErrors } from "@/lib/apiError";
import { EyeIcon, EyeOffIcon } from "@/components/icons/Icons";
import {
  getPersonPhoneNumber,
  resolveStaffPhotoUrl,
  splitStaffName,
} from "@/app/management/staff/staffUtils";

const GENDER_OPTIONS = [
  { value: "male", label: "ذكر" },
  { value: "female", label: "أنثى" },
];

export default function ProfileDrawer({ open, onClose }) {
  const toast = useToast();
  const { data: profileData, isLoading: loadingProfile } = useGetProfileQuery(undefined, {
    skip: !open,
  });
  const user = profileData?.data || {};
  const [changePassword, { isLoading: isChanging }] = useChangePasswordMutation();
  const [updateProfile, { isLoading: isUpdating }] = useUpdateProfileMutation();
  const [logout] = useLogoutMutation();

  const [form, setForm] = useState({
    custom_username: "",
    new_password: "",
    new_password_confirmation: "",
  });

  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  const [formError, setFormError] = useState("");
  const [fieldErrors, setFieldErrors] = useState({});
  const [usernameSuggestions, setUsernameSuggestions] = useState([]);

  // --- Profile edit state ---
  const [editMode, setEditMode] = useState(false);
  const [editForm, setEditForm] = useState({});
  const [editError, setEditError] = useState("");
  const [editFieldErrors, setEditFieldErrors] = useState({});

  useEffect(() => {
    if (!open || !user.custom_username) return;

    setForm((current) =>
      current.custom_username ? current : { ...current, custom_username: user.custom_username },
    );
  }, [user.custom_username, open]);

  function updateField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    if (field === "custom_username") {
      setUsernameSuggestions([]);
    }
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
      phone_number: getPersonPhoneNumber(person),
      gender: person.gender || "",
    });
    setEditError("");
    setEditFieldErrors({});
    setEditMode(true);
  }

  function cancelEdit() {
    setEditMode(false);
    setEditForm({});
    setEditError("");
    setEditFieldErrors({});
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
    if (!editForm.gender) errors.gender = "يرجى اختيار الجنس";

    if (Object.keys(errors).length > 0) {
      setEditFieldErrors(errors);
      return;
    }

    try {
      await updateProfile(createProfileUpdateBody(user.person, editForm)).unwrap();

      toast.success("تم تحديث بيانات الملف الشخصي بنجاح.");
      setEditMode(false);
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
    setUsernameSuggestions([]);

    const userId = Number(user.user_id || user.id);

    try {
      await changePassword({
        user_id: userId,
        new_password: form.new_password,
        new_password_confirmation: form.new_password_confirmation,
        custom_username: form.custom_username,
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
      setFieldErrors(getApiFieldErrors(err));
      const backendSuggestions = err?.data?.data?.suggestions;
      setUsernameSuggestions(
        Array.isArray(backendSuggestions)
          ? backendSuggestions.filter((suggestion) => typeof suggestion === "string")
          : [],
      );
      setFormError(
        err?.data?.message || "تعذر تغيير كلمة المرور. تأكد من صحة البيانات وحاول مرة أخرى.",
      );
    }
  }

  const person = user.person || {};
  const fullName = person.full_name || user.username || "مستخدم تكنوجيم";
  // const email = person.email || "";
  const phone = getPersonPhoneNumber(person);
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
              {currentPhotoUrl ? (
                <img
                  src={currentPhotoUrl}
                  className="h-full w-full object-cover"
                  alt="Profile Avatar"
                />
              ) : (
                avatarLetter
              )}
            </div>
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

            <label className="block text-right text-sm text-app-muted-light">
              رقم الهاتف *
              <input
                type="tel"
                dir="ltr"
                value={editForm.phone_number || ""}
                onChange={(e) => updateEditField("phone_number", e.target.value)}
                placeholder="رقم الهاتف"
                className={`mt-2 h-11 w-full rounded-xl border bg-app-card-soft px-4 text-left text-app-text outline-none transition ${
                  editFieldErrors.phone_number
                    ? "border-app-red focus:border-app-red"
                    : "border-app-line focus:border-app-yellow"
                }`}
              />
              {editFieldErrors.phone_number && (
                <span className="mt-1 block text-xs text-app-red">
                  {editFieldErrors.phone_number}
                </span>
              )}
            </label>

            <label className="block text-right text-sm text-app-muted-light">
              الجنس
              <Dropdown
                className="mt-2 text-app-text"
                buttonClassName="h-11 bg-app-card-soft"
                value={editForm.gender}
                onChange={(value) => updateEditField("gender", value)}
                options={GENDER_OPTIONS}
                placeholder="اختر الجنس"
                error={editFieldErrors.gender}
              />
            </label>

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
                disabled={isUpdating}
              >
                إلغاء
              </Button>
              <Button
                type="submit"
                className="flex-1 text-black"
                loading={isUpdating}
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

            <Button type="button" tone="outline" className="w-full" onClick={startEdit}>
              تعديل البيانات الشخصية
            </Button>
          </>
        )}

        {/* Change Password Form */}
        {!editMode && (
          <div className="border-t border-app-line pt-6">
            <h3 className="text-sm font-semibold text-app-text mb-4">تغيير كلمة المرور</h3>

            <form noValidate onSubmit={handlePasswordSubmit} className="space-y-4">
              <label className="relative block text-right text-sm text-app-muted-light">
                اسم المستخدم
                <input
                  value={form.custom_username}
                  type="text"
                  dir="ltr"
                  autoComplete="username"
                  onChange={(e) => updateField("custom_username", e.target.value)}
                  className={`mt-2 h-11 w-full rounded-xl border bg-app-card-soft px-4 text-left text-app-text outline-none transition ${
                    fieldErrors.custom_username
                      ? "border-app-red focus:border-app-red"
                      : "border-app-line focus:border-app-yellow"
                  }`}
                  placeholder="أدخل اسم المستخدم"
                />
                {fieldErrors.custom_username && (
                  <span className="mt-1 block text-xs text-app-red">
                    {fieldErrors.custom_username}
                  </span>
                )}
              </label>

              {usernameSuggestions.length > 0 && (
                <div aria-label="اقتراحات أسماء المستخدم">
                  <p className="text-xs text-app-muted-light">اقتراحات متاحة:</p>
                  <div className="mt-2 flex flex-wrap gap-2">
                    {usernameSuggestions.map((suggestion) => (
                      <button
                        key={suggestion}
                        type="button"
                        dir="auto"
                        onClick={() => updateField("custom_username", suggestion)}
                        className="rounded-lg border border-app-yellow/40 bg-app-yellow/10 px-2.5 py-1.5 text-xs text-app-yellow transition hover:border-app-yellow hover:bg-app-yellow/20"
                      >
                        {suggestion}
                      </button>
                    ))}
                  </div>
                </div>
              )}

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
