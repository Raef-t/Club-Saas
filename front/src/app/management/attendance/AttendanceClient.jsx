"use client";

import { useMemo, useState, useEffect, useRef } from "react";
import { Html5Qrcode } from "html5-qrcode";
import {
  useQrCheckInMutation,
  useQrCheckOutMutation,
  useGetMemberSubscriptionsQuery,
  useGetMemberQuery,
  useGetMemberAttendancesQuery,
  useDeductAttendanceMutation,
} from "@/lib/api/attendanceApi";
import PageHeader from "@/components/common/PageHeader";
import Button from "@/components/ui/Button";
import DataTable from "@/components/ui/DataTable";
import Dropdown from "@/components/ui/Dropdown";
import ToggleSwitch from "@/components/ui/ToggleSwitch";
import { useToast } from "@/components/ui/Toast";

const tableColumns = [
  { key: "number", label: "الرقم", align: "center", width: "72px" },
  { key: "time", label: "الوقت", align: "center", width: "120px" },
  {
    key: "member",
    label: "العضو",
    align: "center",
    width: "minmax(150px,1fr)",
  },
  {
    key: "activity",
    label: "النشاط",
    align: "center",
    width: "minmax(130px,1fr)",
  },
  {
    key: "coach",
    label: "المدرب",
    align: "center",
    width: "minmax(130px,1fr)",
  },
  { key: "locker", label: "الخزانة", align: "center", width: "96px" },
  {
    key: "duration",
    label: "المدة",
    align: "center",
    width: "96px",
    render: (value) => (value ? `${value} دقيقة` : "-"),
  },
  {
    key: "status",
    label: "الحالة",
    align: "center",
    width: "96px",
    render: (value) => <StatusBadge status={value} />,
  },
];

function QrCodeIcon({ className = "size-5" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <path
        d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Z"
        stroke="currentColor"
        strokeWidth="1.7"
      />
      <path d="M14 14h2.5v2.5H14V14Zm4 0h2v6h-6v-2h4v-4Z" fill="currentColor" />
    </svg>
  );
}

function CameraIcon({ className = "size-12" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <path
        d="M8.5 6 10 4h4l1.5 2H19a2 2 0 0 1 2 2v9.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.5Z"
        stroke="currentColor"
        strokeWidth="1.8"
        strokeLinejoin="round"
      />
      <circle
        cx="12"
        cy="12.7"
        r="3.4"
        stroke="currentColor"
        strokeWidth="1.8"
      />
    </svg>
  );
}

function CheckCircleIcon({ className = "size-5" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.7" />
      <path
        d="m8 12.2 2.5 2.5L16.5 9"
        stroke="currentColor"
        strokeWidth="1.8"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  );
}

function StopIcon({ className = "size-4" }) {
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden="true"
    >
      <rect x="7" y="7" width="10" height="10" rx="2" fill="currentColor" />
    </svg>
  );
}

function StatusBadge({ status }) {
  const isIn = status === "دخول";
  return (
    <span
      className={`inline-flex min-w-16 justify-center rounded-lg px-3 py-1 text-xs font-medium ${
        isIn
          ? "bg-app-green/20 text-[#00dc1a]"
          : "bg-white/10 text-app-muted-light"
      }`}
    >
      {status}
    </span>
  );
}

function InfoRow({ label, value, tone = "default" }) {
  const valueClass =
    tone === "yellow"
      ? "text-app-yellow"
      : tone === "green"
        ? "text-app-green"
        : "text-app-text";

  return (
    <div className="flex items-center justify-between gap-4 text-sm">
      <span className={`min-w-0 truncate font-medium ${valueClass}`}>
        {value || "-"}
      </span>
      <span className="shrink-0 text-app-muted-light">{label}</span>
    </div>
  );
}

function ScannerCard({
  alwaysOn,
  scannerActive,
  scanMode,
  onScanModeChange,
  onAlwaysOnChange,
  onScanClick,
  onScanSuccess,
  onStop,
}) {
  const onScanSuccessRef = useRef(onScanSuccess);

  useEffect(() => {
    onScanSuccessRef.current = onScanSuccess;
  }, [onScanSuccess]);

  useEffect(() => {
    let html5QrCode;
    if (scannerActive) {
      html5QrCode = new Html5Qrcode("qr-reader");
      html5QrCode
        .start(
          { facingMode: "environment" },
          { fps: 10, qrbox: { width: 250, height: 250 } },
          (decodedText) => {
            if (onScanSuccessRef.current) onScanSuccessRef.current(decodedText);
          },
          () => {}, // ignore errors (usually just means no QR code in view)
        )
        .catch((err) => {
          console.error("Camera error:", err);
        });
    }

    return () => {
      if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode
          .stop()
          .then(() => {
            try {
              html5QrCode.clear();
            } catch (e) {
              console.warn("Failed to clear html5QrCode:", e);
            }
          })
          .catch(console.error);
      }
    };
  }, [scannerActive]);

  return (
    <section
      className="w-full max-w-[520px] overflow-hidden rounded-xl border border-app-line bg-app-yellow text-[#1b1b1b]"
      dir="rtl"
    >
      <div className="flex flex-col gap-3 p-3 sm:flex-row sm:items-start sm:justify-between">
        <div className="flex items-center justify-end gap-2 text-right">
          <div>
            <QrCodeIcon className="size-5 shrink-0" />
            <h2 className="text-lg font-medium">قارئ QR</h2>
            <p className="mt-1 text-xs text-[#4b4b4b]">
              {scanMode === "check-in"
                ? "قم بمسح بطاقة العضو لتسجيل الحضور"
                : "قم بمسح بطاقة العضو لتسجيل الخروج"}
            </p>
          </div>
        </div>

        <div className="flex items-center justify-start gap-2 text-[#1b1b1b]">
          <span
            className={`rounded-lg px-2.5 py-1 text-[11px] font-medium ${
              scannerActive
                ? "bg-black/20 text-black"
                : "bg-white/35 text-[#4b4b4b]"
            }`}
          >
            {scannerActive ? "الماسح يعمل" : "جاهز للمسح"}
          </span>
          <span className="text-xs font-medium">تشغيل دائم</span>
          <ToggleSwitch
            checked={alwaysOn}
            onChange={(event) => onAlwaysOnChange(event.target.checked)}
            size="sm"
          />
        </div>
      </div>

      <div className="px-3 pb-3">
        <div className="flex rounded-lg bg-black/10 p-1">
          <button
            type="button"
            onClick={() => onScanModeChange("check-in")}
            className={`flex-1 rounded-md py-1.5 text-xs font-medium transition-all ${
              scanMode === "check-in"
                ? "bg-white text-black shadow-sm font-bold"
                : "text-[#4b4b4b] hover:text-[#1b1b1b]"
            }`}
          >
            تسجيل دخول
          </button>
          <button
            type="button"
            onClick={() => onScanModeChange("check-out")}
            className={`flex-1 rounded-md py-1.5 text-xs font-medium transition-all ${
              scanMode === "check-out"
                ? "bg-white text-black shadow-sm font-bold"
                : "text-[#4b4b4b] hover:text-[#1b1b1b]"
            }`}
          >
            تسجيل خروج
          </button>
        </div>
      </div>

      <div
        onClick={!scannerActive ? onScanClick : undefined}
        className={`group mx-3 mb-3 grid min-h-[145px] w-[calc(100%-1.5rem)] place-items-center overflow-hidden rounded-xl border border-dashed border-white/70 bg-[#957a04] text-[#1b1b1b] transition ${!scannerActive ? "cursor-pointer hover:bg-[#8a7104]" : ""}`}
      >
        <div className="relative flex w-full flex-col items-center justify-center">
          <div
            id="qr-reader"
            className={
              scannerActive
                ? "w-full max-w-[300px] overflow-hidden rounded-lg"
                : "hidden"
            }
          />

          {!scannerActive && (
            <div className="flex flex-col items-center">
              <CameraIcon className="size-12 transition group-hover:scale-105" />
              <span className="mt-3 text-base font-medium">
                انقر لتفعيل قارئ QR
              </span>
              <span className="mt-1 text-xs">
                {alwaysOn
                  ? "سيبقى المسح فعالاً بشكل دائم"
                  : "سيتم المسح عند النقر"}
              </span>
            </div>
          )}
        </div>
      </div>

      {scannerActive && !alwaysOn && (
        <div className="flex justify-end px-3 pb-3">
          <Button
            type="button"
            tone="dark"
            className="h-9 px-3 text-xs"
            icon={<StopIcon />}
            onClick={onStop}
          >
            إيقاف المسح
          </Button>
        </div>
      )}
    </section>
  );
}

function PlayerCard({
  member,
  selectedSubscription,
  selectedActivity,
  subscriptionOptions,
  activityOptions,
  selectedSubscriptionId,
  selectedActivityId,
  lockerNumber,
  isRegistered,
  isPendingDeduction,
  onSubscriptionChange,
  onActivityChange,
  onLockerChange,
  onRegister,
}) {
  const isPlaceholder = !member;

  if (isPlaceholder) {
    return (
      <section
        className="app-card flex flex-col items-center justify-center w-full rounded-2xl p-6 min-h-[300px] border border-dashed border-app-line text-center"
        dir="rtl"
      >
        <div className="flex size-16 items-center justify-center rounded-full bg-app-panel-soft text-app-muted-light">
          <svg
            className="size-8 animate-pulse text-app-muted"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"
            />
          </svg>
        </div>
        <h3 className="mt-4 text-base font-medium text-app-text">
          بانتظار مسح بطاقة اللاعب
        </h3>
        <p className="mt-2 text-xs text-app-muted max-w-[260px] leading-relaxed">
          يرجى استخدام قارئ الـ QR لمسح بطاقة العضو لعرض تفاصيل الاشتراك وتسجيل
          الحضور.
        </p>
      </section>
    );
  }

  const photoSrc = member.photoUrl
    ? member.photoUrl.startsWith("http")
      ? member.photoUrl
      : `http://31.70.108.63/${member.photoUrl.replace(/^\//, "")}`
    : null;

  return (
    <section className="app-card w-full rounded-2xl p-6" dir="rtl">
      <div className="flex items-center justify-center gap-2 text-app-green">
        <span className="text-lg font-medium">
          {isRegistered ? "تم تسجيل الحضور" : "بيانات اللاعب"}
        </span>
        <CheckCircleIcon className="size-5" />
      </div>

      <div className="mt-4 flex flex-col items-center">
        {photoSrc ? (
          <img
            src={photoSrc}
            alt={member.name}
            className="size-24 rounded-full border border-app-line object-cover"
          />
        ) : (
          <div className="grid size-24 place-items-center rounded-full border border-app-line bg-app-card-soft text-3xl font-medium text-app-yellow">
            {member.avatar}
          </div>
        )}
        <h2 className="mt-3 text-xl font-medium text-app-text">
          {member.name}
        </h2>
        <p className="mt-1 text-sm text-app-muted-light" dir="ltr">
          {member.number}
        </p>
      </div>

      <div className="mt-6 space-y-4">
        <InfoRow
          label="النشاط"
          value={selectedActivity?.label || selectedSubscription?.activity}
        />
        <InfoRow
          label="المدرب"
          value={selectedActivity?.coach || selectedSubscription?.coach}
        />
        <InfoRow
          label="الحصص المتبقية"
          value={selectedSubscription?.remaining}
          tone="yellow"
        />
        <InfoRow label="ينتهي" value={selectedSubscription?.endsAt} />
      </div>

      <div className="mt-6 space-y-4 border-t border-app-line pt-5">
        <label className="block text-right text-sm text-app-muted-light">
          اشتراكات اللاعب
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={selectedSubscriptionId}
            onChange={onSubscriptionChange}
            options={subscriptionOptions}
            placeholder="اختر الاشتراك"
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          النشاط اليوم
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={selectedActivityId}
            onChange={onActivityChange}
            options={activityOptions}
            placeholder="اختر النشاط"
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          رقم الخزانة
          <input
            value={lockerNumber}
            onChange={(event) => onLockerChange(event.target.value)}
            className="app-input mt-2 h-11 w-full px-3 text-right outline-none transition focus:border-app-yellow/70"
            placeholder="مثال: 18"
            inputMode="numeric"
          />
        </label>

        <Button type="button" className="h-11 w-full" onClick={onRegister}>
          {isPendingDeduction ? "خصم الجلسة وتأكيد الحضور" : "تسجيل حضور"}
        </Button>
      </div>
    </section>
  );
}

export default function AttendanceClient() {
  const toast = useToast();
  const [qrCheckIn] = useQrCheckInMutation();
  const [qrCheckOut] = useQrCheckOutMutation();
  const [deductAttendance] = useDeductAttendanceMutation();
  const [lastAttendanceId, setLastAttendanceId] = useState(null);
  const [scanMode, setScanMode] = useState("check-in"); // "check-in" | "check-out"
  const [scannedMemberId, setScannedMemberId] = useState(null);
  const { data: memberResponse } = useGetMemberQuery(scannedMemberId, {
    skip: !scannedMemberId,
  });
  const {
    data: memberSubscriptionsResponse,
    error: subsError,
    isLoading: subsLoading,
  } = useGetMemberSubscriptionsQuery(scannedMemberId, {
    skip: !scannedMemberId,
  });
  const { data: memberAttendancesResponse, error: attendancesError, isLoading: attendancesLoading } = useGetMemberAttendancesQuery(scannedMemberId, {
    skip: !scannedMemberId,
  });

  useEffect(() => {
    if (scannedMemberId) {
      console.log("Scanned Member ID:", scannedMemberId);
      console.log("Subscriptions Response:", memberSubscriptionsResponse);
      if (subsError) console.error("Subscriptions Error:", subsError);
    }
  }, [scannedMemberId, memberSubscriptionsResponse, subsError]);
  const [alwaysOn, setAlwaysOn] = useState(false);
  const [scannerActive, setScannerActive] = useState(false);
  const [selectedSubscriptionId, setSelectedSubscriptionId] = useState("");
  const [selectedActivityId, setSelectedActivityId] = useState("");
  const [lockerNumber, setLockerNumber] = useState("");
  const [registeredMemberId, setRegisteredMemberId] = useState(null);
  const [attendanceRows, setAttendanceRows] = useState([]);

  const activeMember = useMemo(() => {
    if (scannedMemberId) {
      const person = memberResponse?.data?.person;
      const fullName = person?.full_name || `عضو #${scannedMemberId}`;
      return {
        id: scannedMemberId,
        name: fullName,
        number: memberResponse?.data?.member_number || `M-${scannedMemberId}`,
        avatar: fullName ? fullName[0] : "ع",
        photoUrl: person?.photo_url || null,
        age: person?.age || "-",
      };
    }
    return null;
  }, [scannedMemberId, memberResponse]);

  const apiAttendanceRows = useMemo(() => {
    if (!memberAttendancesResponse?.data) return [];
    return memberAttendancesResponse.data.map((record) => {
      const checkInTime = record.check_in
        ? new Date(record.check_in).toLocaleTimeString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
            hour12: true,
          }).toLowerCase()
        : "-";

      let statusLabel = "غير معروف";
      if (record.status === "checked_in") statusLabel = "دخول";
      if (record.status === "completed") statusLabel = "مكتمل";

      return {
        id: record.id,
        number: `#${record.id}`,
        time: checkInTime,
        member: activeMember?.name || `عضو #${record.member_id}`,
        activity: "-",
        coach: "-",
        locker: "-",
        status: statusLabel,
        duration: record.duration_minutes || null,
      };
    });
  }, [memberAttendancesResponse, activeMember]);

  const displayRows = scannedMemberId ? apiAttendanceRows : attendanceRows;

  const apiSubscriptions = useMemo(() => {
    if (!memberSubscriptionsResponse?.data) return [];
    return memberSubscriptionsResponse.data.map((sub) => {
      // Map items to activities
      const activities = (sub.items || []).map((item) => ({
        id: item.activity_id,
        label: item.activity_name,
        coach: item.coach?.name || "-",
      }));

      // Fallback if items is empty
      if (activities.length === 0) {
        activities.push({
          id: sub.plan_id,
          label: sub.plan_name,
          coach: "-",
        });
      }

      return {
        id: sub.player_subscription_id,
        memberId: sub.member_id,
        label: sub.plan_name,
        activity: sub.plan_name,
        coach: activities[0]?.coach || "-",
        remaining:
          sub.total_sessions_remaining !== undefined
            ? sub.total_sessions_remaining
            : "-",
        endsAt: sub.end_date,
        activities,
        activeLockers: sub.active_lockers || [],
      };
    });
  }, [memberSubscriptionsResponse]);

  const playerSubscriptions = useMemo(() => {
    return apiSubscriptions;
  }, [apiSubscriptions]);

  useEffect(() => {
    if (scannedMemberId && apiSubscriptions.length > 0) {
      const firstSub = apiSubscriptions[0];
      setSelectedSubscriptionId(firstSub.id);
      setSelectedActivityId(firstSub.activities?.[0]?.id || "");
      const firstLocker = firstSub.activeLockers?.[0]?.locker_number || "";
      setLockerNumber(firstLocker);
    }
  }, [apiSubscriptions, scannedMemberId]);

  const selectedSubscription = useMemo(
    () =>
      playerSubscriptions.find(
        (subscription) => subscription.id === selectedSubscriptionId,
      ) || playerSubscriptions[0],
    [playerSubscriptions, selectedSubscriptionId],
  );

  const activityOptions = useMemo(
    () =>
      (selectedSubscription?.activities || []).map((activity) => ({
        value: activity.id,
        label: activity.label,
      })),
    [selectedSubscription],
  );

  const selectedActivity = useMemo(
    () =>
      selectedSubscription?.activities?.find(
        (activity) => activity.id === selectedActivityId,
      ) || selectedSubscription?.activities?.[0],
    [selectedActivityId, selectedSubscription],
  );

  const subscriptionOptions = useMemo(
    () =>
      playerSubscriptions.map((subscription) => ({
        value: subscription.id,
        label: `${subscription.label} - ${subscription.remaining} حصة`,
      })),
    [playerSubscriptions],
  );

  function handleAlwaysOnChange(checked) {
    setAlwaysOn(checked);
    setScannerActive(checked);
  }

  function handleScanClick() {
    setScannerActive(true);
  }

  function handleScanSuccess(decodedText) {
    if (!alwaysOn) {
      setScannerActive(false);
    }

    if (scanMode === "check-out") {
      qrCheckOut({ qr_code: decodedText })
        .unwrap()
        .then((res) => {
          toast.success(res?.message || "تم تسجيل الخروج بنجاح");

          const duration = res?.data?.duration_minutes || 0;
          const nextId = attendanceRows.length + 1;
          const now = new Date();
          const time = now
            .toLocaleTimeString("en-US", {
              hour: "2-digit",
              minute: "2-digit",
              hour12: true,
            })
            .toLowerCase();

          setAttendanceRows((current) => [
            {
              id: nextId,
              number: `#${nextId}`,
              time,
              member:
                res?.data?.member_name || res?.data?.member_id
                  ? `عضو #${res?.data?.member_id}`
                  : "عضو (من الـ QR)",
              activity: "-",
              coach: "-",
              locker: "-",
              status: "خروج",
              duration: duration,
            },
            ...current,
          ]);
        })
        .catch((err) => {
          toast.error(err?.data?.message || "فشل تسجيل الخروج");
        });
    } else {
      qrCheckIn({ qr_code: decodedText, branch_id: 3 })
        .unwrap()
        .then((res) => {
          toast.success("تم تسجيل الدخول بنجاح");

          const memberId = res?.data?.member_id;
          const attendanceId = res?.data?.attendance_id || res?.data?.id;

          if (memberId) {
            setScannedMemberId(memberId);
          }
          if (attendanceId) {
            setLastAttendanceId(attendanceId);
          }

          const nextId = attendanceRows.length + 1;
          const now = new Date();
          const time = now
            .toLocaleTimeString("en-US", {
              hour: "2-digit",
              minute: "2-digit",
              hour12: true,
            })
            .toLowerCase();

          setAttendanceRows((current) => [
            {
              id: nextId,
              attendanceId: attendanceId,
              number: `#${nextId}`,
              time,
              member: memberId ? `عضو #${memberId}` : "عضو جديد (من الـ QR)",
              activity: "-",
              coach: "-",
              locker: "-",
              status: "دخول",
              duration: null,
            },
            ...current,
          ]);
        })
        .catch((err) => {
          toast.error(err?.data?.message || "فشل تسجيل الدخول");
        });
    }
  }

  function handleSubscriptionChange(value) {
    const subscription = playerSubscriptions.find((item) => item.id === value);
    setSelectedSubscriptionId(value);
    setSelectedActivityId(subscription?.activities?.[0]?.id || "");
    const firstLocker = subscription?.activeLockers?.[0]?.locker_number || "";
    setLockerNumber(firstLocker);
    setRegisteredMemberId(null);
  }

  function handleRegister() {
    if (!activeMember) return;

    if (lastAttendanceId && selectedSubscriptionId) {
      deductAttendance({
        attendanceId: lastAttendanceId,
        body: {
          player_subscription_ids: [Number(selectedSubscriptionId)],
        },
      })
        .unwrap()
        .then((res) => {
          toast.success(res?.message || "تم خصم الجلسة بنجاح وتأكيد الحضور");

          // Update the check-in row in the table with selected activity, coach, locker
          setAttendanceRows((current) =>
            current.map((row) => {
              if (row.attendanceId === lastAttendanceId) {
                return {
                  ...row,
                  member: activeMember.name,
                  activity:
                    selectedActivity?.label ||
                    selectedSubscription?.activity ||
                    "-",
                  coach:
                    selectedActivity?.coach ||
                    selectedSubscription?.coach ||
                    "-",
                  locker: lockerNumber || "-",
                };
              }
              return row;
            }),
          );

          setRegisteredMemberId(activeMember.id);
          setLastAttendanceId(null); // Reset
        })
        .catch((err) => {
          toast.error(err?.data?.message || "فشل خصم الجلسة");
        });
    } else {
      // Fallback for manual check-in when no QR code was scanned
      const nextId = attendanceRows.length + 1;
      const now = new Date();
      const time = now
        .toLocaleTimeString("en-US", {
          hour: "2-digit",
          minute: "2-digit",
          hour12: true,
        })
        .toLowerCase();

      setAttendanceRows((current) => [
        {
          id: nextId,
          number: `#${nextId}`,
          time,
          member: activeMember.name,
          activity:
            selectedActivity?.label || selectedSubscription?.activity || "-",
          coach: selectedActivity?.coach || selectedSubscription?.coach || "-",
          locker: lockerNumber || "-",
          status: "دخول",
        },
        ...current,
      ]);
      setRegisteredMemberId(activeMember.id);
      toast.success("تم تسجيل الحضور.");
    }
  }

  return (
    <div className="space-y-6" dir="rtl">
      <section
        className="grid min-w-0 items-start justify-center gap-6 xl:grid-cols-[minmax(0,420px)_minmax(0,520px)] w-full max-w-5xl mx-auto"
        dir="ltr"
      >
        <PlayerCard
          member={activeMember}
          selectedSubscription={selectedSubscription}
          selectedActivity={selectedActivity}
          subscriptionOptions={subscriptionOptions}
          activityOptions={activityOptions}
          selectedSubscriptionId={selectedSubscriptionId}
          selectedActivityId={selectedActivityId}
          lockerNumber={lockerNumber}
          isRegistered={
            activeMember ? registeredMemberId === activeMember.id : false
          }
          isPendingDeduction={!!lastAttendanceId}
          onSubscriptionChange={handleSubscriptionChange}
          onActivityChange={(value) => {
            setSelectedActivityId(value);
            setRegisteredMemberId(null);
          }}
          onLockerChange={(value) => {
            setLockerNumber(value);
            setRegisteredMemberId(null);
          }}
          onRegister={handleRegister}
        />

        <ScannerCard
          alwaysOn={alwaysOn}
          scannerActive={scannerActive}
          scanMode={scanMode}
          onScanModeChange={setScanMode}
          onAlwaysOnChange={handleAlwaysOnChange}
          onScanClick={handleScanClick}
          onScanSuccess={handleScanSuccess}
          onStop={() => setScannerActive(false)}
        />
      </section>

      <DataTable
        title={scannedMemberId ? "سجل حضور اللاعب" : "سجل الحضور"}
        columns={tableColumns}
        rows={displayRows}
        minWidth="900px"
        tableColumns="72px 120px minmax(150px,1fr) minmax(130px,1fr) minmax(130px,1fr) 96px 96px 96px"
        showAdd={false}
        showSearch={false}
        showFilter={false}
        showExport={false}
        totalPages={0}
        rowClassName="gap-2 px-3 py-4"
        headerClassName="gap-2 px-3"
        getRowKey={(row) => row.id}
        toolbarMeta={
          <div className="flex items-center gap-4">
            <p className="text-sm text-app-muted-light">
              الإجمالي:{" "}
              <span className="font-medium text-app-text">
                {displayRows.length.toLocaleString("ar")} حركة
              </span>
            </p>
            {attendancesError && (
              <p className="text-sm text-red-500">
                خطأ في جلب البيانات: {attendancesError?.status} - {JSON.stringify(attendancesError?.data)}
              </p>
            )}
            {attendancesLoading && (
              <p className="text-sm text-app-yellow">جاري التحميل...</p>
            )}
          </div>
        }
      />
    </div>
  );
}
