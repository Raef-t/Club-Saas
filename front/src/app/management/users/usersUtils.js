export const USER_ROLE_LABELS = {
  super_admin: "مدير النظام",
  admin: "مشرف",
  management_admin: "مدير إداري",
  general_manager: "مدير عام",
  manager: "مدير",
  accountant: "محاسب",
  cashier: "أمين صندوق",
  coach: "مدرب",
  staff: "موظف",
  reception: "استقبال",
  receptionist: "موظف استقبال",
  cleaner: "نظافة",
  gym_staff: "موظف نادي",
  player: "لاعب",
};

const USER_ROLE_TAB_LABELS = {
  super_admin: "مديرو النظام",
  admin: "المشرفون",
  management_admin: "المدراء الإداريون",
  general_manager: "المدراء العامون",
  manager: "المدراء",
  accountant: "المحاسبون",
  cashier: "أمناء الصندوق",
  coach: "المدربون",
  staff: "الموظفون",
  reception: "موظفو الاستقبال",
  receptionist: "موظفو الاستقبال",
  cleaner: "موظفو النظافة",
  gym_staff: "موظفو النادي",
  player: "اللاعبون",
};

export function getUsersCollection(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response)) return response;
  return [];
}

export function getUserRoles(user) {
  if (Array.isArray(user?.roles)) return user.roles.filter(Boolean);
  if (user?.role) return [user.role];
  return [];
}

export function getUserRoleLabel(role) {
  return USER_ROLE_LABELS[role] || String(role || "غير محدد").replaceAll("_", " ");
}

export function getPasswordStatus(user) {
  if (user?.is_password_changed) {
    return {
      label: "تم تغييرها",
      helper: "كلمة المرور محدّثة",
      className: "bg-[rgba(19,172,73,0.16)] text-app-green",
    };
  }

  if (user?.must_change_password) {
    return {
      label: "مطلوب تغييرها",
      helper: "بانتظار المستخدم",
      className: "bg-app-yellow-soft text-app-yellow",
    };
  }

  return {
    label: "لا يلزم تغييرها",
    helper: "الحساب جاهز",
    className: "bg-[rgba(7,85,255,0.14)] text-app-blue",
  };
}

export function filterUsers(users, search) {
  const term = search.trim().toLocaleLowerCase("ar");
  if (!term) return users;

  return users.filter((user) => {
    const values = [
      user.name,
      user.username,
      user.custom_username,
      ...getUserRoles(user),
      ...getUserRoles(user).map(getUserRoleLabel),
    ];

    return values
      .filter(Boolean)
      .some((value) => String(value).toLocaleLowerCase("ar").includes(term));
  });
}

export function createUserRoleOptions(users) {
  const roles = new Set(users.flatMap(getUserRoles));

  return [...roles]
    .sort((a, b) => getUserRoleLabel(a).localeCompare(getUserRoleLabel(b), "ar"))
    .map((role) => ({ value: role, label: getUserRoleLabel(role) }));
}

export function buildUserRoleTabs(users) {
  const roleOptions = createUserRoleOptions(users);

  return [
    { value: "all", label: "الكل" },
    ...roleOptions.map((option) => ({
      ...option,
      label: USER_ROLE_TAB_LABELS[option.value] || option.label,
    })),
  ];
}

export function createUserStats(users, { roleFilter, setRoleFilter } = {}) {
  const hasRole = (user, roles) => getUserRoles(user).some((role) => roles.includes(role));
  const countByRoles = (roles) => users.filter((user) => hasRole(user, roles)).length;
  const needsPasswordChange = users.filter(
    (user) => user.must_change_password && !user.is_password_changed,
  ).length;

  return [
    {
      title: "إجمالي الحسابات",
      value: users.length.toLocaleString("ar"),
      helper: "كل الحسابات المسجلة",
      tone: "yellow",
      iconKey: "members",
      compact: true,
      onClick: setRoleFilter ? () => setRoleFilter("all") : undefined,
      active: roleFilter === "all",
    },
    {
      title: "حسابات الإدارة",
      value: countByRoles(["super_admin", "admin", "management_admin", "manager"]).toLocaleString(
        "ar",
      ),
      helper: "مديرو ومشرفو النظام",
      tone: "purple",
      iconKey: "members",
      compact: true,
      onClick: setRoleFilter
        ? () => setRoleFilter(roleFilter === "admin" ? "all" : "admin")
        : undefined,
      active:
        roleFilter === "admin" ||
        roleFilter === "super_admin" ||
        roleFilter === "management_admin" ||
        roleFilter === "manager",
    },
    {
      title: "حسابات المدربين",
      value: countByRoles(["coach"]).toLocaleString("ar"),
      helper: "المدربون المسجلون",
      tone: "blue",
      iconKey: "coaches",
      compact: true,
      onClick: setRoleFilter
        ? () => setRoleFilter(roleFilter === "coach" ? "all" : "coach")
        : undefined,
      active: roleFilter === "coach",
    },
    {
      title: "حسابات اللاعبين",
      value: countByRoles(["player"]).toLocaleString("ar"),
      helper: "اللاعبون المسجلون",
      tone: "green",
      iconKey: "members",
      compact: true,
      onClick: setRoleFilter
        ? () => setRoleFilter(roleFilter === "player" ? "all" : "player")
        : undefined,
      active: roleFilter === "player",
    },
    {
      title: "بانتظار تغيير كلمة المرور",
      value: needsPasswordChange.toLocaleString("ar"),
      helper: "حسابات تتطلب إجراءً",
      tone: "orange",
      iconKey: "members",
      compact: true,
    },
  ];
}
