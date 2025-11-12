<?php
session_start();
$env = parse_ini_file(__DIR__ . '/.env');

function e($s) { return htmlspecialchars($s, ENT_QUOTES); }
$logged = isset($_SESSION['user']);
$role = $_SESSION['role'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Sync Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    :root {
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --secondary: #ec4899;
      --success: #10b981;
      --error: #ef4444;
      --warning: #f59e0b;
      --dark: #0f172a;
      --dark-light: #1e293b;
      --glass: rgba(255, 255, 255, 0.1);
      --glass-border: rgba(255, 255, 255, 0.2);
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    }
    
    /* Animated Background */
    .background-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      overflow: hidden;
    }
    
    .gradient-orb {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.6;
      animation: float 20s infinite ease-in-out;
    }
    
    .orb1 {
      width: 500px;
      height: 500px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      top: -10%;
      left: -10%;
      animation-delay: 0s;
    }
    
    .orb2 {
      width: 400px;
      height: 400px;
      background: linear-gradient(135deg, #f093fb, #f5576c);
      top: 50%;
      right: -10%;
      animation-delay: 5s;
    }
    
    .orb3 {
      width: 600px;
      height: 600px;
      background: linear-gradient(135deg, #4facfe, #00f2fe);
      bottom: -20%;
      left: 30%;
      animation-delay: 10s;
    }
    
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      33% { transform: translate(30px, -30px) scale(1.1); }
      66% { transform: translate(-20px, 20px) scale(0.9); }
    }
    
    /* Particles */
    .particles {
      position: absolute;
      width: 100%;
      height: 100%;
    }
    
    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(255, 255, 255, 0.6);
      border-radius: 50%;
      animation: particle-float 15s infinite linear;
    }
    
    @keyframes particle-float {
      0% { transform: translateY(0) translateX(0); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { transform: translateY(-100vh) translateX(100px); opacity: 0; }
    }
    
    .container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(40px) saturate(180%);
      padding: 50px;
      border-radius: 30px;
      box-shadow: 
        0 30px 90px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.5);
      width: 100%;
      max-width: 1000px;
      position: relative;
      z-index: 1;
      animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(40px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    
    .header {
      text-align: center;
      margin-bottom: 45px;
      position: relative;
    }
    
    .header-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 20px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      color: white;
      box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
      animation: pulse 3s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4); }
      50% { transform: scale(1.05); box-shadow: 0 15px 60px rgba(99, 102, 241, 0.6); }
    }
    
    h2 {
      font-size: 38px;
      font-weight: 800;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 10px;
      letter-spacing: -1px;
    }
    
    .subtitle {
      font-size: 15px;
      color: #64748b;
      font-weight: 500;
      letter-spacing: 0.5px;
    }
    
    .login-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
      max-width: 450px;
      margin: 0 auto;
    }
    
    .input-group {
      position: relative;
    }
    
    .input-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 18px;
      transition: all 0.3s;
      z-index: 1;
    }
    
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 16px 20px 16px 52px;
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      font-size: 15px;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      background: white;
      font-weight: 500;
      color: #1e293b;
    }
    
    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1), 0 10px 30px rgba(99, 102, 241, 0.1);
      transform: translateY(-2px);
    }
    
    input:focus + .input-icon {
      color: var(--primary);
      transform: translateY(-50%) scale(1.1);
    }
    
    /* Custom Select Dropdown */
    .select-wrapper {
      position: relative;
      flex: 1;
    }
    
    select {
      width: 100%;
      padding: 16px 45px 16px 52px;
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      font-size: 15px;
      font-family: 'Inter', sans-serif;
      font-weight: 600;
      background: white;
      color: #1e293b;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      appearance: none;
    }
    
    select:hover {
      border-color: var(--primary);
      box-shadow: 0 5px 20px rgba(99, 102, 241, 0.15);
    }
    
    select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1), 0 10px 30px rgba(99, 102, 241, 0.15);
      transform: translateY(-2px);
    }
    
    .select-arrow {
      position: absolute;
      right: 18px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      color: #64748b;
      font-size: 14px;
      transition: all 0.3s;
    }
    
    .select-wrapper:hover .select-arrow {
      color: var(--primary);
      transform: translateY(-50%) scale(1.2);
    }
    
    button {
      padding: 16px 32px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      border: none;
      border-radius: 16px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      font-family: 'Inter', sans-serif;
      box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
      position: relative;
      overflow: hidden;
      letter-spacing: 0.5px;
    }
    
    button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }
    
    button:hover {
      transform: translateY(-3px);
      box-shadow: 0 15px 40px rgba(99, 102, 241, 0.5);
    }
    
    button:hover::before {
      left: 100%;
    }
    
    button:active {
      transform: translateY(-1px);
    }
    
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
    }
    
    button i {
      margin-right: 8px;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1));
      border-radius: 20px;
      margin-bottom: 35px;
      border: 2px solid rgba(99, 102, 241, 0.2);
      box-shadow: 0 5px 20px rgba(99, 102, 241, 0.1);
    }
    
    .user-badge {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 800;
      font-size: 20px;
      box-shadow: 0 5px 20px rgba(99, 102, 241, 0.4);
      border: 3px solid white;
    }
    
    .user-details {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    
    .username {
      font-weight: 700;
      font-size: 17px;
      color: #1e293b;
      letter-spacing: -0.3px;
    }
    
    .role {
      font-size: 13px;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .logout-btn {
      padding: 10px 20px;
      font-size: 14px;
      background: white;
      color: var(--primary);
      border: 2px solid var(--primary);
      box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
    }
    
    .logout-btn:hover {
      background: var(--primary);
      color: white;
    }
    
    .section {
      margin-bottom: 30px;
    }
    
    .section-title {
      font-size: 20px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      letter-spacing: -0.5px;
    }
    
    .section-title i {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border-radius: 10px;
      font-size: 16px;
      box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
    }
    
    .action-row {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
      align-items: stretch;
    }
    
    .action-row button {
      flex-shrink: 0;
      min-width: 160px;
    }
    
    .log-container {
      margin-top: 35px;
    }
    
    .log-box {
      background: linear-gradient(135deg, #1e293b, #0f172a);
      color: #e2e8f0;
      padding: 24px;
      border-radius: 20px;
      height: 320px;
      overflow-y: auto;
      font-size: 13px;
      font-family: 'JetBrains Mono', monospace;
      white-space: pre-wrap;
      line-height: 1.7;
      box-shadow: inset 0 4px 20px rgba(0, 0, 0, 0.5), 0 5px 20px rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.1);
      position: relative;
    }
    
    .log-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 40px;
      background: linear-gradient(180deg, rgba(30, 41, 59, 0.8), transparent);
      pointer-events: none;
    }
    
    .log-box::-webkit-scrollbar {
      width: 10px;
    }
    
    .log-box::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
    }
    
    .log-box::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 10px;
      border: 2px solid rgba(30, 41, 59, 0.5);
    }
    
    .log-box::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, var(--secondary), var(--primary));
    }
    
    .hint {
      text-align: center;
      font-size: 13px;
      color: #64748b;
      margin-top: 15px;
      font-weight: 500;
    }
    
    /* Toast System */
    .toast-container {
      position: fixed;
      top: 30px;
      right: 30px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 15px;
      max-width: 420px;
    }
    
    .toast {
      background: white;
      padding: 20px 24px;
      border-radius: 18px;
      box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.5);
      display: flex;
      align-items: center;
      gap: 15px;
      animation: toastIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
      border-left: 5px solid;
      min-width: 350px;
      backdrop-filter: blur(20px);
    }
    
    @keyframes toastIn {
      from {
        opacity: 0;
        transform: translateX(120px) scale(0.9);
      }
      to {
        opacity: 1;
        transform: translateX(0) scale(1);
      }
    }
    
    .toast.removing {
      animation: toastOut 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    
    @keyframes toastOut {
      to {
        opacity: 0;
        transform: translateX(120px) scale(0.9);
      }
    }
    
    .toast.success { border-left-color: var(--success); }
    .toast.error { border-left-color: var(--error); }
    .toast.info { border-left-color: var(--primary); }
    .toast.warning { border-left-color: var(--warning); }
    
    .toast-icon {
      flex-shrink: 0;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 16px;
    }
    
    .toast.success .toast-icon {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }
    
    .toast.error .toast-icon {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }
    
    .toast.info .toast-icon {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
    }
    
    .toast.warning .toast-icon {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }
    
    .toast-content {
      flex: 1;
    }
    
    .toast-title {
      font-weight: 700;
      font-size: 15px;
      color: #1e293b;
      margin-bottom: 4px;
      letter-spacing: -0.3px;
    }
    
    .toast-message {
      font-size: 13px;
      color: #64748b;
      line-height: 1.5;
    }
    
    .toast-close {
      flex-shrink: 0;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #f1f5f9;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      font-size: 18px;
      transition: all 0.2s;
      font-weight: 700;
    }
    
    .toast-close:hover {
      background: #e2e8f0;
      color: #1e293b;
      transform: rotate(90deg);
    }
    
    /* Loading Overlay */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(10px);
      z-index: 9998;
      display: none;
      align-items: center;
      justify-content: center;
    }
    
    .loading-overlay.active {
      display: flex;
    }
    
    .loading-content {
      background: white;
      padding: 40px 50px;
      border-radius: 24px;
      text-align: center;
      box-shadow: 0 30px 90px rgba(0, 0, 0, 0.5);
      animation: scaleIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    @keyframes scaleIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }
    
    .loading-spinner {
      width: 60px;
      height: 60px;
      border: 5px solid #e2e8f0;
      border-top-color: var(--primary);
      border-right-color: var(--secondary);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 25px;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .loading-text {
      font-size: 18px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 8px;
      letter-spacing: -0.3px;
    }
    
    .loading-subtext {
      font-size: 14px;
      color: #64748b;
      font-weight: 500;
    }
    
    /* Locked State */
    .locked {
      pointer-events: none;
      opacity: 0.6;
      position: relative;
      filter: grayscale(50%);
    }
    
    .locked::after {
      content: '🔒';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 48px;
      opacity: 0.4;
      animation: lockPulse 2s infinite;
      z-index: 10;
    }
    
    @keyframes lockPulse {
      0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.4; }
      50% { transform: translate(-50%, -50%) scale(1.15); opacity: 0.6; }
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 35px 25px;
      }
      
      h2 {
        font-size: 28px;
      }
      
      .action-row {
        flex-direction: column;
      }
      
      .toast-container {
        left: 20px;
        right: 20px;
        max-width: none;
      }
      
      .toast {
        min-width: auto;
      }
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }
    
    body::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: moveBackground 20s linear infinite;
    }
    
    @keyframes moveBackground {
      0% { transform: translate(0, 0); }
      100% { transform: translate(50px, 50px); }
    }
    
    .container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 40px;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
      width: 100%;
      max-width: 950px;
      position: relative;
      z-index: 1;
      animation: slideUp 0.6s ease-out;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .header {
      text-align: center;
      margin-bottom: 35px;
      position: relative;
    }
    
    .header::after {
      content: '';
      position: absolute;
      bottom: -15px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2);
      border-radius: 2px;
    }
    
    h2 {
      font-size: 32px;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 8px;
    }
    
    .subtitle {
      font-size: 14px;
      color: #64748b;
      font-weight: 500;
    }
    
    .login-form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-width: 400px;
      margin: 0 auto;
    }
    
    .input-group {
      position: relative;
    }
    
    input[type="text"],
    input[type="password"],
    select {
      width: 100%;
      padding: 14px 16px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 15px;
      font-family: 'Inter', sans-serif;
      transition: all 0.3s ease;
      background: #fff;
      font-weight: 500;
    }
    
    input:focus, select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    
    button {
      padding: 14px 28px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Inter', sans-serif;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
      position: relative;
      overflow: hidden;
    }
    
    button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
    }
    
    button:hover::before {
      left: 100%;
    }
    
    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }
    
    button:active {
      transform: translateY(0);
    }
    
    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      border-radius: 12px;
      margin-bottom: 30px;
      border: 1px solid rgba(102, 126, 234, 0.2);
    }
    
    .user-badge {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 16px;
    }
    
    .user-details {
      display: flex;
      flex-direction: column;
    }
    
    .username {
      font-weight: 600;
      font-size: 15px;
      color: #1e293b;
    }
    
    .role {
      font-size: 13px;
      color: #64748b;
      font-weight: 500;
    }
    
    .logout-btn {
      padding: 8px 16px;
      font-size: 13px;
      background: #fff;
      color: #667eea;
      border: 2px solid #667eea;
      box-shadow: none;
    }
    
    .logout-btn:hover {
      background: #667eea;
      color: #fff;
    }
    
    .section {
      margin-bottom: 25px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .section-title::before {
      content: '';
      width: 4px;
      height: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 2px;
    }
    
    .action-row {
      display: flex;
      gap: 12px;
      margin-bottom: 12px;
      align-items: stretch;
    }
    
    .action-row select {
      flex: 1;
    }
    
    .action-row button {
      flex-shrink: 0;
    }
    
    .log-container {
      margin-top: 30px;
    }
    
    .log-box {
      background: #1e293b;
      color: #e2e8f0;
      padding: 20px;
      border-radius: 12px;
      height: 280px;
      overflow-y: auto;
      font-size: 13px;
      font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Courier New', monospace;
      white-space: pre-wrap;
      line-height: 1.6;
      box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .log-box::-webkit-scrollbar {
      width: 8px;
    }
    
    .log-box::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 4px;
    }
    
    .log-box::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 4px;
    }
    
    .log-box::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.3);
    }
    
    .hint {
      text-align: center;
      font-size: 13px;
      color: #64748b;
      margin-top: 12px;
      font-weight: 500;
    }
    
    /* Toast System */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-width: 400px;
    }
    
    .toast {
      background: #fff;
      padding: 16px 20px;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideIn 0.3s ease-out;
      border-left: 4px solid;
      min-width: 320px;
    }
    
    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(100px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }
    
    .toast.removing {
      animation: slideOut 0.3s ease-out forwards;
    }
    
    @keyframes slideOut {
      to {
        opacity: 0;
        transform: translateX(100px);
      }
    }
    
    .toast.success {
      border-left-color: #10b981;
    }
    
    .toast.error {
      border-left-color: #ef4444;
    }
    
    .toast.info {
      border-left-color: #3b82f6;
    }
    
    .toast-icon {
      flex-shrink: 0;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
    }
    
    .toast.success .toast-icon {
      background: #d1fae5;
      color: #10b981;
    }
    
    .toast.error .toast-icon {
      background: #fee2e2;
      color: #ef4444;
    }
    
    .toast.info .toast-icon {
      background: #dbeafe;
      color: #3b82f6;
    }
    
    .toast-content {
      flex: 1;
    }
    
    .toast-title {
      font-weight: 600;
      font-size: 14px;
      color: #1e293b;
      margin-bottom: 2px;
    }
    
    .toast-message {
      font-size: 13px;
      color: #64748b;
      line-height: 1.4;
    }
    
    .toast-close {
      flex-shrink: 0;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: #f1f5f9;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      font-size: 16px;
      transition: all 0.2s;
    }
    
    .toast-close:hover {
      background: #e2e8f0;
      color: #1e293b;
    }
    
    /* Loading Spinner */
    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
      margin-left: 8px;
      vertical-align: middle;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      z-index: 9998;
      display: none;
      align-items: center;
      justify-content: center;
    }
    
    .loading-overlay.active {
      display: flex;
    }
    
    .loading-content {
      background: #fff;
      padding: 30px 40px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #e2e8f0;
      border-top-color: #667eea;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 20px;
    }
    
    .loading-text {
      font-size: 16px;
      font-weight: 600;
      color: #1e293b;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 30px 20px;
      }
      
      h2 {
        font-size: 24px;
      }
      
      .action-row {
        flex-direction: column;
      }
      
      .toast-container {
        left: 20px;
        right: 20px;
        max-width: none;
      }
      
      .toast {
        min-width: auto;
      }
    }
  </style>
</head>
<body>
<!-- Animated Background -->
<div class="background-animation">
  <div class="gradient-orb orb1"></div>
  <div class="gradient-orb orb2"></div>
  <div class="gradient-orb orb3"></div>
  <div class="particles" id="particles"></div>
</div>

<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-content">
    <div class="loading-spinner"></div>
    <div class="loading-text">Processing...</div>
    <div class="loading-subtext">Please wait while we sync your database</div>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<div class="container">
  <div class="header">
    <div class="header-icon animate__animated animate__bounceIn">
      <i class="fas fa-database"></i>
    </div>
    <h2>Database Sync Panel</h2>
    <div class="subtitle">Enterprise-grade database synchronization</div>
  </div>

<?php if(!$logged): ?>
  <div class="login-form">
    <div class="input-group">
      <i class="fas fa-user input-icon"></i>
      <input id="username" type="text" placeholder="Username" autocomplete="username">
    </div>
    <div class="input-group">
      <i class="fas fa-lock input-icon"></i>
      <input id="password" type="password" placeholder="Password" autocomplete="current-password">
    </div>
    <button id="loginBtn" onclick="login()">
      <i class="fas fa-sign-in-alt"></i>Sign In
    </button>
    <div class="hint">Use admin / goldy / piyush / tejas credentials</div>
  </div>
<?php else: ?>
  <div class="user-info">
    <div class="user-badge">
      <div class="avatar"><?=strtoupper(substr($_SESSION['user'], 0, 1))?></div>
      <div class="user-details">
        <div class="username"><?=e($_SESSION['user'])?></div>
        <div class="role"><i class="fas fa-shield-alt"></i> <?=e($role)?> account</div>
      </div>
    </div>
    <button class="logout-btn" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i>Logout
    </button>
  </div>

  <?php if($role === 'admin'): ?>
    <div class="section" id="adminSection">
      <div class="section-title">
        <i class="fas fa-user-shield"></i>
        Admin Actions
      </div>
      <div class="action-row">
        <div class="select-wrapper">
          <i class="fas fa-database input-icon"></i>
          <select id="userToMain">
            <option value="goldy"><i class="fas fa-user"></i> Goldy → Main Database</option>
            <option value="piyush"><i class="fas fa-user"></i> Piyush → Main Database</option>
            <option value="tejas"><i class="fas fa-user"></i> Tejas → Main Database</option>
          </select>
          <i class="fas fa-chevron-down select-arrow"></i>
        </div>
        <button id="toMainBtn" onclick="sync('toMain')">
          <i class="fas fa-arrow-up"></i>Sync to Main
        </button>
      </div>
      <div class="action-row">
        <div class="select-wrapper">
          <i class="fas fa-database input-icon"></i>
          <select id="mainToUser">
            <option value="goldy">Main Database → Goldy</option>
            <option value="piyush">Main Database → Piyush</option>
            <option value="tejas">Main Database → Tejas</option>
          </select>
          <i class="fas fa-chevron-down select-arrow"></i>
        </div>
        <button id="toUserBtn" onclick="sync('toUser')">
          <i class="fas fa-arrow-down"></i>Sync to User
        </button>
      </div>
    </div>
  <?php else: ?>
    <div class="section" id="userSection">
      <div class="section-title">
        <i class="fas fa-user"></i>
        User Actions
      </div>
      <div class="action-row">
        <button id="mainToMineBtn" onclick="sync('mainToMine')" style="flex:1;">
          <i class="fas fa-sync-alt"></i>Sync from Main Database
        </button>
        <button id="downloadBtn" onclick="downloadMyDB()" style="flex:1; background: linear-gradient(135deg, #10b981, #059669);">
          <i class="fas fa-download"></i>Download My Database
        </button>
      </div>
    </div>
  <?php endif; ?>

  <div class="log-container">
    <div class="section-title">
      <i class="fas fa-terminal"></i>
      Activity Log
    </div>
    <div class="log-box" id="log"><span style="color: #64748b;"># Waiting for operations...</span></div>
  </div>
<?php endif; ?>
</div>

<script>
let csrfToken = '';
let isSyncing = false;

// Create animated particles
function createParticles() {
  const particles = document.getElementById('particles');
  for (let i = 0; i < 30; i++) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
    particle.style.animationDelay = Math.random() * 5 + 's';
    particles.appendChild(particle);
  }
}

// Initialize particles on load
window.addEventListener('DOMContentLoaded', createParticles);

// Toast Notification System
function showToast(title, message, type = 'info') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast ${type} animate__animated animate__fadeInRight`;
  
  const icons = {
    success: '<i class="fas fa-check-circle"></i>',
    error: '<i class="fas fa-times-circle"></i>',
    info: '<i class="fas fa-info-circle"></i>',
    warning: '<i class="fas fa-exclamation-triangle"></i>'
  };
  
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || icons.info}</div>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" onclick="removeToast(this)">×</button>
  `;
  
  container.appendChild(toast);
  
  // Auto remove after 6 seconds
  setTimeout(() => {
    removeToast(toast.querySelector('.toast-close'));
  }, 6000);
}

function removeToast(btn) {
  const toast = btn.closest('.toast');
  toast.classList.add('removing');
  setTimeout(() => toast.remove(), 300);
}

// Loading overlay
function showLoading(text = 'Processing...', subtext = 'Please wait while we sync your database') {
  const overlay = document.getElementById('loadingOverlay');
  overlay.querySelector('.loading-text').textContent = text;
  overlay.querySelector('.loading-subtext').textContent = subtext;
  overlay.classList.add('active');
}

function hideLoading() {
  document.getElementById('loadingOverlay').classList.remove('active');
}

// Lock/Unlock sections
function lockSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (section) {
    section.classList.add('locked');
  }
}

function unlockSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (section) {
    section.classList.remove('locked');
  }
}

// Fetch CSRF token
async function fetchCSRFToken() {
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=getToken'
    });
    const data = await res.json();
    if (data.success) {
      csrfToken = data.message;
    }
  } catch(e) {
    console.error('Failed to fetch CSRF token:', e);
  }
}

<?php if($logged): ?>
// Fetch CSRF token on page load
fetchCSRFToken();
<?php endif; ?>

// Login function
async function login() {
  const btn = document.getElementById('loginBtn');
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  
  if (!username || !password) {
    showToast('Validation Error', 'Please enter both username and password', 'error');
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Signing in...';
  showLoading('Authenticating...', 'Verifying your credentials');
  
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    });
    
    const data = await res.json();
    
    if (data.success) {
      showToast('Welcome!', 'Login successful, redirecting...', 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      hideLoading();
      showToast('Login Failed', data.message, 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-sign-in-alt"></i>Sign In';
    }
  } catch(e) {
    hideLoading();
    showToast('Error', 'Login request failed: ' + e.message, 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i>Sign In';
  }
}

// Logout function
function logout() {
  if (!confirm('Are you sure you want to logout?')) return;
  
  showLoading('Logging out...', 'Ending your session');
  fetch('actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=logout'
  }).finally(() => {
    showToast('Logged Out', 'You have been logged out successfully', 'success');
    setTimeout(() => location.reload(), 800);
  });
}

// Download function
function downloadMyDB() {
  showToast('Download Started', 'Preparing your database export...', 'info');
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'actions.php';
  form.style.display = 'none';
  
  const actionInput = document.createElement('input');
  actionInput.name = 'action';
  actionInput.value = 'download';
  form.appendChild(actionInput);
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

// Append log message
function appendLog(message, type = 'info') {
  const log = document.getElementById('log');
  const timestamp = new Date().toLocaleTimeString();
  const colors = {
    info: '#3b82f6',
    success: '#10b981',
    error: '#ef4444',
    warning: '#f59e0b'
  };
  
  const color = colors[type] || colors.info;
  const logLine = `<span style="color: #64748b;">[${timestamp}]</span> <span style="color: ${color};">${message}</span>\n`;
  
  log.innerHTML += logLine;
  log.scrollTop = log.scrollHeight;
}

// Sync function with background execution and real-time log streaming
async function sync(type) {
  if (isSyncing) {
    showToast('Operation In Progress', 'Please wait for the current sync to complete', 'warning');
    return;
  }
  
  const btnId = type === 'toMain' ? 'toMainBtn' : (type === 'toUser' ? 'toUserBtn' : 'mainToMineBtn');
  const btn = document.getElementById(btnId);
  const log = document.getElementById('log');
  
  if (!csrfToken) {
    showToast('Security Error', 'Security token missing. Please refresh the page.', 'error');
    return;
  }
  
  const body = {action: type, csrf: csrfToken};
  if (type === 'toMain') body.user = document.getElementById('userToMain').value;
  if (type === 'toUser') body.user = document.getElementById('mainToUser').value;
  
  const formData = new URLSearchParams(body).toString();
  
  // Lock UI immediately
  isSyncing = true;
  const sectionId = type === 'mainToMine' ? 'userSection' : 'adminSection';
  lockSection(sectionId);
  
  // Disable ALL sync buttons
  disableAllSyncButtons();
  
  // Update current button to show spinner
  const originalHTML = btn.innerHTML;
  btn.innerHTML = btn.innerHTML.replace(/<i[^>]*><\/i>/, '<i class="fas fa-spinner fa-spin"></i>');
  
  // Clear log and show initial message
  log.innerHTML = '';
  appendLog('🚀 Initializing sync operation...', 'info');
  
  try {
    // Start the sync operation (runs in background on server)
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: formData
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const data = await res.json();
    
    if (data.success) {
      // Display the complete log from the server
      log.innerHTML = '';
      const logLines = data.message.split('\n').filter(line => line.trim());
      logLines.forEach(line => {
        if (line.includes('✓') || line.includes('✅')) {
          appendLog(line, 'success');
        } else if (line.includes('❌') || line.includes('⚠')) {
          appendLog(line, 'error');
        } else {
          appendLog(line, 'info');
        }
      });
      
      showToast('Sync Successful!', 'Database synchronization completed successfully', 'success');
      
      // Refresh CSRF token
      await fetchCSRFToken();
    } else {
      log.innerHTML = '';
      appendLog('❌ ' + data.message, 'error');
      showToast('Sync Failed', data.message, 'error');
    }
  } catch(e) {
    const errorMsg = `Error: ${e.message}`;
    log.innerHTML = '';
    appendLog('❌ ' + errorMsg, 'error');
    showToast('Request Failed', errorMsg, 'error');
  } finally {
    // Unlock UI and re-enable buttons
    isSyncing = false;
    unlockSection(sectionId);
    enableAllSyncButtons();
    btn.innerHTML = originalHTML;
  }
}

// Disable all sync buttons during operation
function disableAllSyncButtons() {
  const buttons = [
    document.getElementById('toMainBtn'),
    document.getElementById('toUserBtn'),
    document.getElementById('mainToMineBtn')
  ];
  
  buttons.forEach(btn => {
    if (btn) {
      btn.disabled = true;
      btn.style.opacity = '0.5';
      btn.style.cursor = 'not-allowed';
    }
  });
}

// Re-enable all sync buttons after operation
function enableAllSyncButtons() {
  const buttons = [
    document.getElementById('toMainBtn'),
    document.getElementById('toUserBtn'),
    document.getElementById('mainToMineBtn')
  ];
  
  buttons.forEach(btn => {
    if (btn) {
      btn.disabled = false;
      btn.style.opacity = '1';
      btn.style.cursor = 'pointer';
    }
  });
}

// Enter key support for login
document.addEventListener('DOMContentLoaded', function() {
  const passwordField = document.getElementById('password');
  const usernameField = document.getElementById('username');
  
  if (passwordField) {
    passwordField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') login();
    });
  }
  
  if (usernameField) {
    usernameField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') document.getElementById('password').focus();
    });
  }
});

function showToast(title, message, type = 'info') {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  
  const icons = {
    success: '✓',
    error: '✕',
    info: 'ℹ'
  };
  
  toast.innerHTML = `
    <div class="toast-icon">${icons[type] || icons.info}</div>
    <div class="toast-content">
      <div class="toast-title">${title}</div>
      <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close" onclick="removeToast(this)">×</button>
  `;
  
  container.appendChild(toast);
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    removeToast(toast.querySelector('.toast-close'));
  }, 5000);
}

function removeToast(btn) {
  const toast = btn.closest('.toast');
  toast.classList.add('removing');
  setTimeout(() => toast.remove(), 300);
}

// Loading overlay
function showLoading(text = 'Processing...') {
  const overlay = document.getElementById('loadingOverlay');
  overlay.querySelector('.loading-text').textContent = text;
  overlay.classList.add('active');
}

function hideLoading() {
  document.getElementById('loadingOverlay').classList.remove('active');
}

// Fetch CSRF token
async function fetchCSRFToken() {
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'action=getToken'
    });
    const data = await res.json();
    if (data.success) {
      csrfToken = data.message;
    }
  } catch(e) {
    console.error('Failed to fetch CSRF token:', e);
  }
}

<?php if($logged): ?>
// Fetch CSRF token on page load
fetchCSRFToken();
<?php endif; ?>

// Login function
async function login() {
  const btn = document.getElementById('loginBtn');
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value.trim();
  
  if (!username || !password) {
    showToast('Validation Error', 'Please enter username and password', 'error');
    return;
  }
  
  btn.disabled = true;
  btn.innerHTML = 'Signing in...<span class="spinner"></span>';
  showLoading('Authenticating...');
  
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    });
    
    const data = await res.json();
    
    if (data.success) {
      showToast('Success!', 'Login successful, redirecting...', 'success');
      setTimeout(() => location.reload(), 800);
    } else {
      hideLoading();
      showToast('Login Failed', data.message, 'error');
      btn.disabled = false;
      btn.innerHTML = 'Sign In';
    }
  } catch(e) {
    hideLoading();
    showToast('Error', 'Login request failed: ' + e.message, 'error');
    btn.disabled = false;
    btn.innerHTML = 'Sign In';
  }
}

// Logout function
function logout() {
  if (!confirm('Are you sure you want to logout?')) return;
  
  showLoading('Logging out...');
  fetch('actions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=logout'
  }).finally(() => {
    showToast('Logged Out', 'You have been logged out successfully', 'success');
    setTimeout(() => location.reload(), 500);
  });
}

// Download function
function downloadMyDB() {
  showToast('Download Started', 'Preparing your database export...', 'info');
  
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'actions.php';
  form.style.display = 'none';
  
  const actionInput = document.createElement('input');
  actionInput.name = 'action';
  actionInput.value = 'download';
  form.appendChild(actionInput);
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

// Sync function with proper AJAX
async function sync(type) {
  const btnId = type === 'toMain' ? 'toMainBtn' : (type === 'toUser' ? 'toUserBtn' : 'mainToMineBtn');
  const btn = document.getElementById(btnId);
  const log = document.getElementById('log');
  
  if (!csrfToken) {
    showToast('Error', 'Security token missing. Please refresh the page.', 'error');
    return;
  }
  
  const body = {action: type, csrf: csrfToken};
  if (type === 'toMain') body.user = document.getElementById('userToMain').value;
  if (type === 'toUser') body.user = document.getElementById('mainToUser').value;
  
  const formData = new URLSearchParams(body).toString();
  
  // Disable button and show loading
  btn.disabled = true;
  const originalText = btn.innerHTML;
  btn.innerHTML = btn.innerHTML.replace(/🔄|📥/, '') + '<span class="spinner"></span>';
  
  showLoading('Synchronizing database...');
  log.textContent = `[${new Date().toLocaleTimeString()}] Starting sync operation...\n`;
  
  try {
    const res = await fetch('actions.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: formData
    });
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const data = await res.json();
    
    hideLoading();
    
    // Update log with timestamp
    const timestamp = new Date().toLocaleTimeString();
    log.textContent = `[${timestamp}] ${data.message}`;
    
    // Scroll log to bottom
    log.scrollTop = log.scrollHeight;
    
    if (data.success) {
      showToast('Sync Successful!', 'Database synchronization completed successfully', 'success');
      
      // Refresh CSRF token after successful operation
      await fetchCSRFToken();
    } else {
      showToast('Sync Failed', data.message, 'error');
    }
  } catch(e) {
    hideLoading();
    const errorMsg = `Error: ${e.message}`;
    log.textContent = `[${new Date().toLocaleTimeString()}] ${errorMsg}`;
    showToast('Request Failed', errorMsg, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
}

// Enter key support for login
document.addEventListener('DOMContentLoaded', function() {
  const passwordField = document.getElementById('password');
  const usernameField = document.getElementById('username');
  
  if (passwordField) {
    passwordField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') login();
    });
  }
  
  if (usernameField) {
    usernameField.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') document.getElementById('password').focus();
    });
  }
});
</script>
</body>
</html>
