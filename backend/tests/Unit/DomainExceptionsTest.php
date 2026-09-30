<?php

namespace Tests\Unit;

use App\Exceptions\ExamAlreadyAttemptedException;
use App\Exceptions\ExamHasAttemptsException;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * As exceções de domínio sabem se renderizar como 409 com a mensagem certa.
 */
class DomainExceptionsTest extends TestCase
{
    public function test_tentativa_duplicada_vira_409(): void
    {
        $response = (new ExamAlreadyAttemptedException)->render(Request::create('/'));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(['message' => 'Este aluno já realizou esta prova.'], $response->getData(true));
    }

    public function test_prova_respondida_tem_mensagens_distintas_para_edicao_e_exclusao(): void
    {
        $update = ExamHasAttemptsException::forUpdate()->render(Request::create('/'));
        $delete = ExamHasAttemptsException::forDelete()->render(Request::create('/'));

        $this->assertSame(409, $update->getStatusCode());
        $this->assertSame(409, $delete->getStatusCode());
        $this->assertSame('Esta prova já foi respondida e não pode ser editada.', $update->getData(true)['message']);
        $this->assertSame('Esta prova já foi respondida e não pode ser excluída.', $delete->getData(true)['message']);
    }
}
