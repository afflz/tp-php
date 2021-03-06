<?php

namespace Controller;

/**
 * Gerencia as rotas de criação de entidades
 *
 * @author asantos07
 */
class Register extends DefaultController {

    public function __invoke($request, $response, $args) {
        $parsedBody = $request->getParsedBody();
        $entidade = \Helper\Validator::validadeCreate($args['type'], $parsedBody);
        if ($entidade == null) {
            return $response->withStatus(400);
        } elseif (\Persistence\Persist::readObject($entidade->getId(), $entidade->getExt())) {
            return $response->withStatus(409);
        } else {
            $entidade->flush();
            if ($parsedBody['senha']) {
                $filename = md5($entidade->getId()) . md5($entidade->getExt());
                $file = fopen(CRED . $filename, "w");
                fwrite($file, md5($parsedBody['senha']));
                fclose($file);
                \Helper\Mailer::registrationConfirm($entidade);
            } elseif ($parsedBody['empresaId']) {
                $empresa = \Persistence\Persist::readObject($parsedBody['empresaId'], \Entity\Empresa::getExt());
                $empresa->addVaga($entidade);
                $empresa->flush();
            }
            \Persistence\AutoIncrement::increment($entidade->getExt());
            return $response->withStatus(201);
        }
    }

}
