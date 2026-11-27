import { Route, Routes } from "react-router-dom";
import AppLayout from "./components/AppLayout";
import ProtectedRoute from "./components/ProtectedRoute";
import Dashboard from "./pages/Dashboard";
import Login from "./pages/Login";
import NotFound from "./pages/NotFound";
import Members from "./pages/Members";
import MemberDetail from "./pages/MemberDetail";
import CheckIn from "./pages/CheckIn";
import Reports from "./pages/Reports";
import Plans from "./pages/Plans";
import Users from "./pages/Users";

export default function App() {
    return (
        <Routes>
            <Route path="/login" element={<Login />} />

            <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                    <Route index element={<Dashboard />} />
                    <Route path="members" element={<Members />} />
                    <Route path="members/:id" element={<MemberDetail />} />
                    <Route element={<ProtectedRoute roles={["admin"]} />}>
                        <Route path="plans" element={<Plans />} />
                        <Route path="users" element={<Users />} />
                    </Route>
                    <Route path="reports" element={<Reports />} />
                    <Route path="*" element={<NotFound />} />
                </Route>
            </Route>
        </Routes>
    );
}
