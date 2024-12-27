import typescript from "@rollup/plugin-typescript";

export default {
  plugins: [typescript()],
  input: "dev/gd-assistant.ts",
  output: {
    file: "assets/gd-assistant.js",
    format: "esm",
  },
};
