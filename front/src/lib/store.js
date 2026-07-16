import { configureStore } from "@reduxjs/toolkit";
import { authApi } from "@/lib/api/authApi";
import { playerSubscriptionsApi } from "@/lib/api/playerSubscriptionsApi";
import { subscriptionPlansApi } from "@/lib/api/subscriptionPlansApi";
import { clubsApi } from "@/lib/api/clubsApi";
import { branchesApi } from "@/lib/api/branchesApi";
import { coachesApi } from "@/lib/api/coachesApi";
import { activitiesApi } from "@/lib/api/activitiesApi";
import { membersApi } from "@/lib/api/membersApi";
import { lockersApi } from "@/lib/api/lockersApi";
import { attendanceApi } from "@/lib/api/attendanceApi";
import { scheduleApi } from "@/lib/api/scheduleApi";

export const store = configureStore({
  reducer: {
    [authApi.reducerPath]: authApi.reducer,
    [playerSubscriptionsApi.reducerPath]: playerSubscriptionsApi.reducer,
    [subscriptionPlansApi.reducerPath]: subscriptionPlansApi.reducer,
    [clubsApi.reducerPath]: clubsApi.reducer,
    [branchesApi.reducerPath]: branchesApi.reducer,
    [coachesApi.reducerPath]: coachesApi.reducer,
    [activitiesApi.reducerPath]: activitiesApi.reducer,
    [membersApi.reducerPath]: membersApi.reducer,
    [lockersApi.reducerPath]: lockersApi.reducer,
    [attendanceApi.reducerPath]: attendanceApi.reducer,
    [scheduleApi.reducerPath]: scheduleApi.reducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(
      authApi.middleware,
      playerSubscriptionsApi.middleware,
      subscriptionPlansApi.middleware,
      clubsApi.middleware,
      branchesApi.middleware,
      coachesApi.middleware,
      activitiesApi.middleware,
      membersApi.middleware,
      lockersApi.middleware,
      attendanceApi.middleware,
      scheduleApi.middleware,
    ),
});
