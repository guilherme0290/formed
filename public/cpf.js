document.addEventListener("DOMContentLoaded", () => {
    const cpfInputs = document.querySelectorAll('input[name="cpf"]');

    cpfInputs.forEach(input => {
        // Máscara
        input.addEventListener("input", () => {
            input.value = maskCPF(input.value);
        });

        // Validação ao sair do campo
        input.addEventListener("blur", () => {
            if (!isValidCPF(input.value)) {
                input.classList.add("border-red-500");
                showCPFError(input, "CPF inválido");
            } else {
                input.classList.remove("border-red-500");
                clearCPFError(input);
            }
        });
    });
});

function maskCPF(value) {
    return value
        .replace(/\D/g, "")                   // remove tudo que não for número
        .replace(/(\d{3})(\d)/, "$1.$2")       // 000.00000000
        .replace(/(\d{3})(\d)/, "$1.$2")       // 000.000.00000
        .replace(/(\d{3})(\d{1,2})$/, "$1-$2");// 000.000.000-00
}

// Validador oficial de CPF
function isValidCPF(cpf) {
    cpf = cpf.replace(/\D/g, "");

    if (cpf.length !== 11) return false;

    if (/^(\d)\1+$/.test(cpf)) return false; // evita 111.111.111-11

    let soma = 0, resto;

    for (let i = 1; i <= 9; i++)
        soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);

    resto = (soma * 10) % 11;
    if ((resto === 10) || (resto === 11)) resto = 0;
    if (resto !== parseInt(cpf.substring(9, 10))) return false;

    soma = 0;
    for (let i = 1; i <= 10; i++)
        soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);

    resto = (soma * 10) % 11;
    if ((resto === 10) || (resto === 11)) resto = 0;

    return resto === parseInt(cpf.substring(10, 11));
}

// Mensagens de erro (simples e reaproveitável)
function showCPFError(input, message) {
    let error = input.parentNode.querySelector(".cpf-error");
    if (!error) {
        error = document.createElement("p");
        error.className = "cpf-error text-red-500 text-xs mt-1";
        input.parentNode.appendChild(error);
    }
    error.textContent = message;
}

function clearCPFError(input) {
    const error = input.parentNode.querySelector(".cpf-error");
    if (error) error.remove();
}
