export default function FormField({
    label,
    name,
    error,
    as: Tag = "input",
    ...props
}) {
    const border = error
        ? "border-red-400 focus:border-red-500 focus:ring-red-500"
        : "border-gray-300 focus:border-indigo-500 focus:ring-indigo-500";

    return (
        <div>
            <label
                htmlFor={name}
                className="block text-sm font-medium text-gray-700">
                {label}
            </label>
            <Tag
                id={name}
                name={name}
                className={`mt-1 w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-1 ${border}`}
                {...props}
            />
            {error && <p className="mt-1 text-xs text-red-600">{error[0]}</p>}
        </div>
    );
}
